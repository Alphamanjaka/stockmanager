# pg_auto_failover (template de laboratoire)

Ce dossier contient un `compose.yaml` de référence pour un cluster pg_auto_failover à 2 nœuds (primary + standby) + 1 monitor.

> Ce template est prévu pour un **test/laboratoire**. Pour la production, il faut :
> - Disposer des nœuds sur **2 serveurs physiques/VM distincts** (le point bloquant : la base de secours doit être sur un autre serveur pour un vrai failover).
> - Changer `--auth trust` pour un mot de passe (`--auth scram-sha-256` + secrets) et restreindre les accès via `--pg-hba-lan`/règles firewall.
> - Utiliser des certificats TLS pour chiffrer la réplication.
> - Persister les données sur des disques dédiés (volumes/partitions) et sauvegarder le monitor.
> - Envisager un troisième nœud pour le monitor si vous souhaitez une vraie résilience (ou le séparer des DB).

## Démarrage

```bash
docker compose -f deploy/pg-ha/compose.yaml up -d
```

## Vérifier l'état du cluster

```bash
docker compose -f deploy/pg-ha/compose.yaml exec monitor pg_autoctl show state
docker compose -f deploy/pg-ha/compose.yaml exec monitor pg_autoctl show formation
docker compose -f deploy/pg-ha/compose.yaml exec monitor pg_autoctl show uri --formation default
```

## Obtenir la chaîne de connexion multi-hôtes pour l'application

`pg_autoctl show uri --formation default` doit retourner une chaîne du type :

```
postgres://node1:5432,node2:5432/app?target_session_attrs=read-write&sslmode=require
```

Dans l'application, on traduit cela en variables d'environnement :

```
DB_HOST=node1,node2  # ou noms/IP de vos deux serveurs
DB_TARGET_SESSION_ATTRS=read-write
DB_CONNECT_TIMEOUT=5
```

## Effectuer un test de failover

1. Déterminer le nœud primaire (status "primary") via `show state`.
2. Forcer un failover via le monitor :

```bash
docker compose -f deploy/pg-ha/compose.yaml exec monitor pg_autoctl perform failover
```

3. Surveiller la transition :

```bash
docker compose -f deploy/pg-ha/compose.yaml exec monitor pg_autoctl watch
```

4. Vérifier que l'application (multi-hôtes + `target_session_attrs=read-write`) se reconnecte automatiquement au nouveau primaire.

## Maintenance

- Mettre un nœud en maintenance : `pg_autoctl enable maintenance --pgdata ...` (ou via commandes équivalentes)
- Sortir un nœud de maintenance : `pg_autoctl disable maintenance`
- Switchover planifié : `pg_autoctl perform switchover`

## Sauvegardes (remarques)

- La **règle d'or** : pour un cluster HA, ne pas déposer l'intégralité des dumps quotidiens sur le **primaire**. Privilégier l'exécution des sauvegardes (logiques/archivage WAL) depuis un **standby** afin de ne pas impacter les écritures.
- Le module d'archive `app:run-backup` reste fonctionnel (invisible dans l'interface, lancé par scheduler + file). Avec un host multi-hôtes (`DB_HOST=node1,node2`), l'outil `pg_dump` (utilisé par Spatie/db-dumper) accepte la liste multi-hôtes et tentera de se connecter au premier accessible ; en conjonction avec `target_session_attrs=read-write` côté app, ce n'est pas le même critère. Pour éviter des effets de bord, en HA réel, il est recommandé de faire pointer la sauvegarde vers un seul nœud (standby ou primary résolu), ou d'utiliser **pgBackRest** (archivage WAL + base backup) — beaucoup plus approprié à un contexte HA+PITR.
- Conserver des archives WAL est indispensable : un failover ne protège **pas** contre une erreur humaine (suppression accidentelle de données) ; seul l'archivage WAL permet une restauration à un point antérieur à l'incident.

## Réseau (HA réel)

- Pour deux serveurs : placer `node1` sur serveur A, `node2` sur serveur B, `monitor` sur un troisième serveur ou sur l'un des deux (mais attention au SPOF).
- L'application Laravel doit se connecter via `DB_HOST=nodeA,nodeB` (IPs/DNS), `DB_TARGET_SESSION_ATTRS=read-write`, `DB_CONNECT_TIMEOUT=5`.
- Firewalls : autoriser la réplication + pg_autoctl sur les ports requis entre les nœuds.

## Liens

- pg_auto_failover docs : https://pg-auto-failover.readthedocs.io/
- pg_auto_failover tutoriaux : https://pg-auto-failover.readthedocs.io/en/main/tutorial.html
