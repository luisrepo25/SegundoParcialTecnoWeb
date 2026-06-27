# Supabase — 2do Parcial TecnoWeb

## Datos de conexión

| Campo    | Valor                                        |
|----------|----------------------------------------------|
| Host     | db.epbvzdstchkqvgbcwmwe.supabase.co          |
| Port     | 5432                                         |
| Database | postgres                                     |
| Username | postgres                                     |
| Password | 2do_ParcialTecnoWeb                          |
| Región   | South America (São Paulo)                    |
| URL      | https://epbvzdstchkqvgbcwmwe.supabase.co     |

## .env (bloque DB)

```env
DB_CONNECTION=pgsql
DB_HOST=db.epbvzdstchkqvgbcwmwe.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=2do_ParcialTecnoWeb
```

## Subir backup a Supabase

```powershell
& "C:\Program Files\PostgreSQL\17\bin\psql.exe" "postgresql://postgres:2do_ParcialTecnoWeb@db.epbvzdstchkqvgbcwmwe.supabase.co:5432/postgres" -f "C:\Users\USUARIO\backup_supabase.sql"
```

## Verificar conexión desde Laravel

```powershell
php artisan config:clear && php artisan cache:clear

php artisan tinker --execute="echo DB::table('carreras')->count() . ' carreras | ' . DB::table('materias')->count() . ' materias | ' . DB::table('usuarios')->count() . ' usuarios';"
```

## Regenerar backup limpio (si cambia la BD origen)

```powershell
# 1. Exportar desde servidor del ing
pg_dump "postgresql://grupo07sc:grup007grup007*@mail.tecnoweb.org.bo:5432/db_grupo07sc" > C:\Users\USUARIO\backup.sql

# 2. Limpiar para Supabase (PowerShell)
$content = Get-Content 'C:\Users\USUARIO\backup.sql' -Raw -Encoding UTF8
$content = $content -replace '\\restrict [^\r\n]+[\r\n]*', ''
$content = $content -replace '\\unrestrict [^\r\n]+[\r\n]*', ''
$content = $content -replace 'ALTER SCHEMA public OWNER TO grupo07sc;[\r\n]*', ''
$content = $content -replace "COMMENT ON SCHEMA public IS '';[\r\n]*", ''
$content = $content -replace 'REVOKE USAGE ON SCHEMA public FROM PUBLIC;[\r\n]*', ''
$content = $content -replace 'OWNER TO grupo07sc', 'OWNER TO postgres'
$content | Set-Content 'C:\Users\USUARIO\backup_supabase.sql' -Encoding UTF8
```
