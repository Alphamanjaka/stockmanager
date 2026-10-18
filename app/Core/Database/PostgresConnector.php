<?php

namespace App\Core\Database;

use Illuminate\Database\Connectors\PostgresConnector as BasePostgresConnector;

/**
 * Connecteur PostgreSQL étendu pour la haute disponibilité (pg_auto_failover).
 *
 * Laravel transmet déjà `host` tel quel : une liste d'hôtes séparés par des
 * virgules (`DB_HOST=pg-node1,pg-node2`) permet à libpq d'essayer chaque nœud
 * dans l'ordre et de basculer automatiquement après un failover.
 *
 * Ce connecteur ajoute en plus deux paramètres libpq au DSN, optionnels :
 *  - target_session_attrs=read-write : ne retient qu'une connexion acceptant
 *    l'écriture, c'est-à-dire le nœud primaire (évite d'atterrir sur un secours).
 *  - connect_timeout : borne l'attente sur un hôte injoignable avant de passer
 *    au suivant.
 */
class PostgresConnector extends BasePostgresConnector
{
    protected function getDsn(array $config)
    {
        $dsn = parent::getDsn($config);

        foreach (['target_session_attrs', 'connect_timeout'] as $option) {
            if (! empty($config[$option])) {
                $dsn .= ";{$option}={$config[$option]}";
            }
        }

        return $dsn;
    }
}
