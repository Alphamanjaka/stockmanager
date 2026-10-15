# Haute disponibilité PostgreSQL avec pg_auto_failover

## Contexte

Précédemment, ce projet proposait un « miroir » de base de données (`app_backup`) rafraîchi par un `pg_dump` nocturne, avec une page `/admin/backups` dédiée à sa gestion. Ce besoin a évolué : l'objectif est désormais de **bascule automatique** (failover) en cas de panne du serveur de production.

Quelques points clefs :
- Le secours **doit être sur un autre serveur/instance PostgreSQL** pour un vrai failover. Si les deux bases sont sur le même serveur, tout disparaît si ce serveur tombe.
- Il faut que l'application bascule **automatiquement** sans changer de code ni d'URL de connexion.
- La sauvegarde « archive ZIP » (fichiers + dump) reste utile en arrière-plan (filet de sécurité, PITR potentiel), mais elle ne doit **pas** être visible dans l'interface.

## Architecture choisie

- **Réplication physique en continu** (streaming WAL) via **pg_auto_failover** (monitor + 2 nœuds primary/standby).
- **Application sans conscience de la bascule** : utilisation d'une **liste d'hôtes multi-hôtes** dans `DB_HOST` (libpq) + `target_session_attrs=read-write` pour ne se connecter qu'au nœud primaire.
- **Connecteur Laravel étendu** (`app/Core/Database/PostgresConnector.php`) : ajoute `target_session_attrs` et `connect_timeout` au DSN — sans casser les environnements dev/test (paramètres optionnels).
- **Archive silencieuse** : `app:run-backup` (Spatie) continue de tourner en tâche de fond (scheduler + worker), sans UI. L'historique `backup_runs` est conservé à des fins d'audit.

## Application Laravel

### Configuration (.env)

En production/HA :

```env
DB_CONNECTION=pgsql
DB_HOST=nodeA,nodeB
DB_TARGET_SESSION_ATTRS=read-write
DB_CONNECT_TIMEOUT=5
```

- `DB_HOST` peut contenir plusieurs hôtes séparés par des virgules (ordre : essaye dans l'ordre indiqué).
- `DB_TARGET_SESSION_ATTRS=read-write` force libpq à sélectionner uniquement le nœud **primaire** (peuple `SHOW transaction_read_only = 'off'`).
- `DB_CONNECT_TIMEOUT` limite le délai d'attente avant d'essayer le nœud suivant.

### Comportement au failover

1. Le primaire devient indisponible → pg_auto_failover promeut le standby en primary.
2. À la prochaine requête HTTP (ou à la prochaine ouverture de connexion), l'application tente les hôtes dans `DB_HOST`. libpq, avec `target_session_attrs=read-write`, élimine les connexions vers le nœud maintenant en **standby** (lecture seule) et s'oriente vers le nouveau primaire.
3. Avec `PHP-FPM` sans connexions persistantes (`CONN_MAX_AGE` à 0), les connexions sont créées par requête : la bascule est donc transparente la plupart du temps. Si vous utilisez des connexions persistantes, vous pourriez observer des erreurs transitoires au moment exact du failover ; un retry côté applicatif ou un ajustement de `CONN_MAX_AGE` peut être nécessaire.

### Connecteur étendu

Le connecteur est enregistré dans `App\Providers\AppServiceProvider::register()` :

```php
$this->app->bind('db.connector.pgsql', fn () => new \App\Core\Database\PostgresConnector);
```

Il étend `Illuminate\Database\Connectors\PostgresConnector` et ajoute au DSN :

- `target_session_attrs=read-write` si `DB_TARGET_SESSION_ATTRS` est défini
- `connect_timeout=X` si `DB_CONNECT_TIMEOUT` est défini

Tests associés : `tests/Unit/Database/PostgresConnectorTest.php`, `tests/Feature/Core/DatabaseConnectorTest.php`.

## pg_auto_failover

### Architecture minimale

- **Monitor** : nœud PostgreSQL avec l'extension `pgautofailover`, gère l'état et la promotion (idéalement sur un 3e serveur).
- **node1 (primary)** : serveur PostgreSQL, accepte les écritures.
- **node2 (standby)** : réplique physique, promu en cas de panne de node1.

### Configuration app → cluster

Après déploiement, récupérer la chaîne multi-hôtes via le monitor :

```bash
pg_autoctl show uri --formation default
```

Exemple :

```
postgres://node1:5432,node2:5432/app?target_session_attrs=read-write&sslmode=require
```

Transformer en variables d'environnement Laravel comme ci-dessus.

### Mise en place

Un template de laboratoire est fourni : `deploy/pg-ha/compose.yaml` + `deploy/pg-ha/README.md`.

Voir ce README pour :
- Démarrage avec Docker Compose (lab)
- Vérification (`pg_autoctl show state`)
- Test de failover (`pg_autoctl perform failover`)
- Notes de production (certificats, auth, firewall, monitoring)

## Archive silencieuse (conservée)

- **Commande** : `app:run-backup` (archive ZIP fichiers + dump SQL, via Spatie Laravel Backup) — toujours planifiée.
- **Scheduler** : journalisée dans `storage/logs/backup.log`, exécutée par le worker si via queue.
- **Historique** : table `backup_runs` (type `archive`) avec statuts running/success/failed + timestamps. Les listeners `MarkArchiveBackupSuccessful/Failed` mettent à jour cet historique.
- **Aucun UI** : plus d'entrée dans le menu, plus de routes, contrôleurs, vues. Purement en arrière-plan.
- **Dumper avec multi-hôtes** : `pg_dump` supporte la liste multi-hôtes via `--host=a,b --port=5432`. En contexte HA, il est **recommandé** d'effectuer les sauvegardes à partir du **standby** (moindre impact sur le primaire). Pour un usage prod, **pgBackRest** (archivage WAL + base backup) est l'option la plus robuste.

## Ce qui a été supprimé

Afin d'aligner avec l'approche « failover infra » et de respecter la demande « ne doit pas être vue dans l'interface », ont été retirés :

- UI /admin/backups (vue, routes, contrôleur, lien menu, clé de traduction)
- Miroir pg_dump (`DatabaseMirrorService`, `app:mirror-database`, `config/backup-database.php`, planification à 01:30, tests associés, variables `BACKUP_DB_*`)
- Méthodes UI de `ArchiveBackupService` (download/delete/verify/cleanOldBackups non utilisés)
- Tests liés au miroir et aux routes UI

Le module `app/Modules/Backup` subsiste donc sous forme **silencieuse** : commandes + listeners + service d'archive + modèle historique.

## Tests

Suite complète : **176 tests, 601 assertions — tous passent**. Les tests unitaires/feature spécifiques au nouveau connecteur et au service d'archive réduits passent également.

## Migration / transition

1. En production, arrêter le **scheduler/worker** le temps de mettre à jour le code.
2. Mettre à jour `.env` pour utiliser la liste multi-hôtes et `DB_TARGET_SESSION_ATTRS=read-write`.
3. Déployer le cluster pg_auto_failover sur deux serveurs (ou selon votre infra).
4. Vérifier `pg_autoctl show state` et `show uri --formation default`.
5. Relancer scheduler/worker. L'archive nocturne reste active ; aucune interaction UI n'est nécessaire.

## Remarques importantes

- **Serveur différent impératif** : le standby doit être sur un autre hôte. C'est la seule manière d'avoir un vrai failover si le serveur primaire tombe.
- **point-in-time** : le failover ne protège pas contre une suppression/erreur humaine. Conservez l'archivage WAL (pgBackRest/wal-g) à côté de l'archive ZIP.
- **Connexions persistantes** : si `CONN_MAX_AGE>0`, les connexions PHP peuvent rester ouvertes vers l'ancien primaire ; la bascule est automatique à l'ouverture de la prochaine connexion (donc idéalement `CONN_MAX_AGE=0` ou gérer les reconnects).
- **Monitor** : le monitor est un SPOF si unique. Pour une HA complète, déployer un petit cluster pour le monitor (ou le protéger/monitorer séparément).
