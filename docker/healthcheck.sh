#!/bin/bash
# Vérifier que les processus Supervisor tournent
if supervisorctl status | grep -q "RUNNING"; then
    exit 0
else
    exit 1
fi
