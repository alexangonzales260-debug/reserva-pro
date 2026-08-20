#!/usr/bin/env bash
# validate.sh — Auto-verificación de la fábrica ReservaPro.
# Código 0 si todo pasa; código distinto si algo falla.
# Sin falsos positivos: solo detecta llamadas de función reales en app/.

set -u

FAIL=0
PASS_COUNT=0

step() {
    echo ""
    echo "### $1"
}

fail() {
    FAIL=1
    echo "  [FAIL] $1"
}

ok() {
    PASS_COUNT=$((PASS_COUNT + 1))
    echo "  [OK] $1"
}

step "1. php artisan --version"
if php artisan --version; then
    ok "artisan responde"
else
    fail "artisan --version falló"
fi

step "2. .env y vendor/ cubiertos por .gitignore"
if grep -qE '^\.env$' .gitignore; then
    ok ".env está en .gitignore"
else
    fail ".env NO está en .gitignore"
fi
if grep -qE '^/vendor$|^vendor/?$' .gitignore; then
    ok "vendor/ está en .gitignore"
else
    fail "vendor/ NO está en .gitignore"
fi

step "3. ./vendor/bin/pint --test"
if ./vendor/bin/pint --test; then
    ok "Pint: estilo PSR-12 correcto"
else
    fail "Pint: hay archivos fuera de estilo"
fi

step "4. Migraciones limpias en memoria (gate pre-test)"
if php artisan migrate:fresh --seed --force --env=testing; then
    ok "Migraciones + seeders OK en sqlite :memory:"
else
    fail "Migraciones + seeders fallaron"
    exit 1
fi

step "5. Lógica de conflictos implementada"
if grep -rq "CheckReservationConflict" app/; then
    ok "Action de conflictos detectado"
else
    fail "Falta Action de conflictos"
    exit 1
fi

step "6. Base multi-tenant: NegocioScope presente"
if grep -rq "class NegocioScope" app/; then
    ok "NegocioScope detectado"
else
    fail "Falta NegocioScope"
    exit 1
fi

step "7. Scope aplicado en modelos tenant"
if grep -rqE "BelongsToNegocio|NegocioScope" app/Models/Service.php app/Models/Employee.php app/Models/Reservation.php; then
    ok "Service/Employee/Reservation usan BelongsToNegocio/NegocioScope"
else
    fail "Falta scope tenant en modelos"
    exit 1
fi

step "8. Middleware SetCurrentNegocio presente y registrado"
if grep -rq "class SetCurrentNegocio" app/Http/Middleware/ && grep -rq "SetCurrentNegocio" bootstrap/app.php; then
    ok "Middleware de negocio actual registrado"
else
    fail "Falta middleware SetCurrentNegocio"
    exit 1
fi

step "9. php artisan test"
if php artisan test; then
    ok "Tests verdes"
else
    fail "Tests en rojo"
fi

step "10. APIs prohibidas en app/ (debe devolver NADA)"
OUT=$(grep -rnE '\b(eval|exec|shell_exec|system|passthru|proc_open)[[:space:]]*\(' app/ 2>/dev/null)
if [ -z "$OUT" ]; then
    ok "Sin APIs prohibidas"
else
    fail "APIs prohibidas encontradas:"
    echo "$OUT"
fi

step "11. dd()/dump() en app/ (debe devolver NADA)"
OUT=$(grep -rnE '\b(dd|dump)[[:space:]]*\(' app/ 2>/dev/null)
if [ -z "$OUT" ]; then
    ok "Sin dd()/dump() en app/"
else
    fail "dd()/dump() encontrados:"
    echo "$OUT"
fi

step "12. Rutas API v1 (debe detectar al menos 10)"
API_ROUTES=$(php artisan route:list --path=api/v1 2>/dev/null | grep -c "api/v1" || true)
if [ "$API_ROUTES" -ge 10 ]; then
    ok "Rutas API detectadas: $API_ROUTES (mínimo 10)"
else
    fail "Solo $API_ROUTES rutas API (se requieren al menos 10)"
    exit 1
fi

echo ""
echo "=========================================="
if [ "$FAIL" -eq 0 ]; then
    echo "VALIDATE OK — $PASS_COUNT comprobaciones superadas (código 0)"
    exit 0
else
    echo "VALIDATE FAIL — revisar errores anteriores (código 1)"
    exit 1
fi