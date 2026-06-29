# Conectar Claude Code desde el Celular al Repositorio

Esta guía resume los pasos realizados para conectar Claude Code (desde un dispositivo móvil) al repositorio `luisrepo25/SegundoParcialTecnoWeb`.

---

## Requisitos Previos

- Cuenta en [claude.ai](https://claude.ai)
- Acceso al repositorio en GitHub (`luisrepo25/SegundoParcialTecnoWeb`)
- Aplicación móvil de Claude o acceso a [claude.ai/code](https://claude.ai/code) desde el navegador del celular

---

## Pasos Realizados

### 1. Acceder a Claude Code en el celular

1. Abre la aplicación de Claude en tu celular o ingresa a [claude.ai](https://claude.ai) desde el navegador.
2. Navega a la sección **Claude Code** (ícono de terminal/código).

---

### 2. Crear una nueva sesión con acceso al repositorio

1. En Claude Code, selecciona **"New Session"** o **"Nueva Sesión"**.
2. Cuando se te pida conectar un repositorio, elige **GitHub** como fuente.
3. Autoriza el acceso de Claude Code a tu cuenta de GitHub si aún no lo has hecho.
4. Busca y selecciona el repositorio: `luisrepo25/SegundoParcialTecnoWeb`.

---

### 3. Configuración del entorno remoto

Claude Code crea un entorno de ejecución remoto (contenedor en la nube) con:

- El repositorio clonado automáticamente en `/home/user/SegundoParcialTecnoWeb`
- Git configurado apuntando a `origin` → `https://github.com/luisrepo25/SegundoParcialTecnoWeb`
- Rama de trabajo asignada: `claude/mobile-claude-repo-setup-6rea1r`

> **Nota:** Cada sesión crea un contenedor efímero nuevo. Los cambios que no se hagan commit y push se perderán al cerrar la sesión.

---

### 4. Rama de trabajo designada

Claude Code asigna automáticamente una rama de desarrollo dedicada para no afectar `main` directamente:

```
claude/mobile-claude-repo-setup-6rea1r
```

Para verificar la rama activa:

```bash
git branch
```

---

### 5. Flujo de trabajo desde el celular

Una vez conectado, el flujo típico es:

1. **Hacer cambios** — Pedirle a Claude que modifique archivos, corrija bugs, agregue features, etc.
2. **Commit** — Claude hace commit con un mensaje descriptivo:
   ```bash
   git commit -m "descripción del cambio"
   ```
3. **Push** — Claude sube los cambios a GitHub:
   ```bash
   git push -u origin claude/mobile-claude-repo-setup-6rea1r
   ```
4. **Pull Request** — Si se necesita fusionar con `main`, se crea un PR desde GitHub o pidiéndoselo a Claude.

---

### 6. Acceso de GitHub limitado por sesión

El acceso de Claude Code a GitHub en esta sesión está limitado únicamente al repositorio:

```
luisrepo25/SegundoParcialTecnoWeb
```

No puede leer ni escribir en otros repositorios.

---

## Consideraciones Importantes

| Aspecto | Detalle |
|---|---|
| Entorno | Contenedor efímero en la nube (se destruye al cerrar sesión) |
| Persistencia | Solo lo que se hace **commit + push** queda guardado en GitHub |
| Rama protegida | `main` — no se hace push directo |
| Rama de trabajo | `claude/mobile-claude-repo-setup-6rea1r` |
| Acceso de red | Outbound HTTPS a través de proxy del agente |
| Herramientas disponibles | Git, PHP, Composer, Node.js, npm, Chromium (Playwright) |

---

## Recursos

- Documentación oficial de Claude Code en la web: [code.claude.com/docs](https://code.claude.com/docs/en/claude-code-on-the-web)
- Repositorio del proyecto: [github.com/luisrepo25/SegundoParcialTecnoWeb](https://github.com/luisrepo25/SegundoParcialTecnoWeb)
