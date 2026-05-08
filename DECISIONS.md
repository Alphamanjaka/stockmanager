# Décisions Architecturales - Système de Settings

## Vue d'ensemble

Le système de settings permet de gérer les configurations globales de l'application (logo, nom de société, paramètres généraux, etc.) de manière centralisée et performante.

---

## 1. Architecture générale

### Composants clés

```
┌─────────────────────────────────────────┐
│  Fichier helpers.php                    │
│  - Fonction globale: settings()          │
└────────────┬────────────────────────────┘
             │ appelle
             ▼
┌─────────────────────────────────────────┐
│  SettingService (Services/)              │
│  - Gestion DB + Cache                   │
│  - get(), getAllSettings()              │
│  - updateSettings()                     │
└────────────┬────────────────────────────┘
             │ accède à
             ▼
┌─────────────────────────────────────────┐
│  Setting Model (Models/)                │
│  - Table: settings                      │
│  - Colonnes: key, value, group, type    │
└─────────────────────────────────────────┘
```

---

## 2. Fonctionnement détaillé

### 2.1 Helper `settings()` dans `app/Helpers/helpers.php`

```php
function settings($key = null, $default = null)
{
    if (is_null($key)) {
        return app(SettingService::class);  // Retourne le service complet
    }

    return app(SettingService::class)->get($key, $default);  // Retourne une valeur spécifique
}
```

**Modes d'utilisation:**

- `settings()` → Retourne l'instance du SettingService (accès complet)
- `settings('logo_path')` → Retourne la valeur associée à la clé 'logo_path'
- `settings('logo_path', 'default.png')` → Retourne la valeur ou la valeur par défaut

### 2.2 SettingService - Gestion des données

#### **Méthode: `getAllSettings()`**
```php
public function getAllSettings()
{
    return Cache::rememberForever('settings.all', function () {
        return Setting::all()->pluck('value', 'key');
    });
}
```

**Fonctionnement:**
- Récupère TOUS les settings depuis le cache
- Si le cache n'existe pas, charge depuis la BD et le stocke indéfiniment
- Retourne un array `['key' => 'value', ...]`
- **Avantage:** Une seule requête BD à la première utilisation, puis accès mémoire

#### **Méthode: `get($key, $default = null)`**
```php
public function get(string $key, $default = null)
{
    $allSettings = $this->getAllSettings();
    return $allSettings[$key] ?? $default;
}
```

**Fonctionnement:**
- Appelle `getAllSettings()` (cache ou requête)
- Recherche la clé dans le tableau
- Retourne la valeur ou la valeur par défaut

#### **Méthode: `updateSettings(array $data)`**
```php
public function updateSettings(array $data)
{
    foreach ($data as $key => $value) {
        // Skip internal fields (_token, _method)
        
        // Gère les uploads de fichiers (logo)
        if ($value instanceof UploadedFile) {
            $this->handleFileUpload($key, $value);
            continue;
        }

        // Crée ou met à jour le setting
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    // ⚠️ CRUCIAL: Vide le cache pour refléter les changements
    Cache::forget('settings.all');
}
```

**Fonctionnement:**
1. Itère sur chaque paramètre
2. Ignore les champs internes (`_token`, `_method`)
3. Détecte les fichiers uploadés et les traite séparément
4. Met à jour ou crée le setting en BD
5. **Vide le cache** pour que les prochains appels récupèrent les nouvelles données

#### **Méthode: `handleFileUpload($key, UploadedFile $file)`**
```php
protected function handleFileUpload($key, UploadedFile $file): void
{
    // Supprime l'ancien fichier s'il existe
    $oldFile = $this->get($key);
    if ($oldFile && Storage::disk('public')->exists($oldFile)) {
        Storage::disk('public')->delete($oldFile);
    }

    // Stocke le nouveau fichier et enregistre le chemin
    $path = $file->store('settings', 'public');
    Setting::updateOrCreate(['key' => $key], ['value' => $path]);
}
```

**Fonctionnement:**
1. Récupère le chemin du fichier existant
2. Supprime l'ancien fichier du disque public
3. Stocke le nouveau fichier dans `storage/app/public/settings/`
4. Sauvegarde le chemin en BD

---

## 3. Raisons de cette architecture

### 3.1 **Centralisation**
- ✅ Tous les settings accessibles depuis un seul point
- ✅ Facile à maintenir et à étendre

### 3.2 **Performance - Cache permanent**
- ✅ Réduit les requêtes BD drastiquement
- ✅ `Cache::rememberForever()` = cache persistant entre les requêtes
- ❌ Nécessite de vider le cache lors d'une mise à jour

### 3.3 **Helper fonction globale**
- ✅ Disponible partout via `settings()`
- ✅ Pas besoin de dependency injection
- ✅ Syntaxe simple et lisible

### 3.4 **Flexibilité des types**
- ✅ Supports strings, numbers, booleans, paths
- ✅ Gère les uploads de fichiers automatiquement
- ✅ Colonne `type` disponible pour validation future

### 3.5 **Sécurité**
- ✅ Filtre les champs internes (`_token`, `_method`)
- ✅ Nettoyage des anciens fichiers
- ✅ Utilisation de `updateOrCreate()` pour éviter les doublons

---

## 4. Modèle de données

### Table `settings`

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | bigint | Clé primaire |
| `key` | string | Identifiant unique du setting (ex: 'logo_path') |
| `value` | text | Valeur du setting |
| `group` | string (nullable) | Groupe logique (ex: 'branding', 'email') |
| `type` | string (nullable) | Type de données (ex: 'string', 'file', 'boolean') |
| `created_at` | timestamp | Date de création |
| `updated_at` | timestamp | Date de modification |

### Exemple de données

```
key                 | value                           | group    | type
─────────────────────────────────────────────────────────────────────
app_name            | Stock Manager                   | branding | string
logo_path           | settings/logo_20260508.png     | branding | file
company_email       | contact@company.com            | email    | string
invoice_footer      | Merci de votre confiance       | invoice  | string
```

---

## 5. Flux d'utilisation

### 5.1 **Lecture simple**
```php
// Dans un contrôleur, une view, ou n'importe où
$appName = settings('app_name');
$logo = settings('logo_path', 'default-logo.png');
```

### 5.2 **Mise à jour depuis un formulaire**
```php
// Dans un contrôleur
public function updateSettings(Request $request)
{
    settings()->updateSettings($request->validated());
    return redirect()->with('success', 'Settings mis à jour');
}
```

### 5.3 **Accès au service complet**
```php
// Accès à toutes les méthodes
$allSettings = settings()->getAllSettings();
settings()->updateSettings(['key' => 'value']);
```

---

## 6. Points critiques à retenir

### ✅ Points forts
1. **Une seule requête BD** pour charger tous les settings
2. **Cache transparent** - pas de complexité visible
3. **Helper globale** - accessible partout
4. **Gestion de fichiers** - automatique et sécurisée

### ⚠️ Points d'attention
1. **Cache permanent** - doit être vidé manuellement lors de mise à jour
2. **Toutes les données en mémoire** - pas idéal si très nombreux settings
3. **Pas de validation** - à implémenter au niveau du contrôleur/request
4. **Pas d'historique** - les modifications ne sont pas enregistrées

### 🔄 Invalidation du cache
Le cache est vidé automatiquement dans `updateSettings()`:
```php
Cache::forget('settings.all');
```

Si vous modifiez la BD directement (migration, seeder), videz manuellement:
```php
Cache::forget('settings.all');
```

---

## 7. Cas d'usage courant

```php
// 1. Charger les settings au démarrage
$companyName = settings('app_name');
$logo = settings('logo_path');

// 2. Afficher un formulaire de paramètres
$allSettings = settings()->getAllSettings();

// 3. Mettre à jour les paramètres
settings()->updateSettings([
    'app_name' => 'Nouveau Nom',
    'logo_path' => $uploadedFile,
    'company_email' => 'new@email.com'
]);
```

---

## 8. Extensions possibles

- [ ] Ajouter une validation par `type`
- [ ] Chiffrer les valeurs sensibles
- [ ] Ajouter un historique des modifications
- [ ] Grouper les settings par catégories en interface
- [ ] Implémenter un cache Redis pour scalabilité
- [ ] Ajouter des paramètres par utilisateur ou tenant

---

**Dernière mise à jour:** Mai 2026
