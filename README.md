# MaybeChat

Chat en tiempo real entre usuarios registrados. Cada persona crea su cuenta (usuario y contraseña), busca a otra
por su nombre de usuario y conversan al instante, con mensajes no leídos y estado en línea.

## Ejecutarlo en tu computador
Necesitas Node 20 o superior.

```bash
cd miServidor
npm install
npm start
```
Abre http://localhost:3000, crea una cuenta y entra. Para probar el chat abre una segunda ventana en modo
incógnito (o en otro navegador), crea otro usuario y búscalo con el botón de nuevo chat.

Pruebas: `npm test` (en la raíz o en `miServidor`).

## Publicarlo en internet (Render)
1. Sube el repo a GitHub y entra en https://render.com con tu cuenta de GitHub.
2. **New → Blueprint**, elige este repositorio. Render lee `render.yaml` y crea el servicio con un disco
   persistente de 1 GB (la base `maybechat.db` vive ahí; sin disco se perdería en cada reinicio). Requiere el plan
   de pago `starter`.
3. Cuando termine el despliegue, Render te da un link tipo `https://maybechat.onrender.com`. Ese es el link que
   compartes: cada persona se registra y ya puede chatear con las demás.

### Variables de entorno
| Variable | Para qué sirve | Por defecto |
|---|---|---|
| `PORT` | Puerto del servidor (Render lo define) | `3000` |
| `DB_FILE` | Ruta del archivo SQLite | `miServidor/maybechat.db` |
| `TRUST_PROXY` | Cantidad de proxies delante del servidor (para el límite de intentos por IP). En Render: `1` | desactivado |

### Notas de seguridad
- Las contraseñas se guardan con bcrypt y los tokens de sesión solo como hash. Las sesiones duran 7 días y
  sobreviven a reinicios.
- Hay límite de intentos de registro/login por IP y de mensajes por minuto por usuario.
- El servidor solo publica el frontend (`index.html`, `style.css`, `js/`, `img/`, `Session/`), nunca la carpeta
  del servidor ni la base de datos.
- Render sirve HTTPS por ti. Si lo alojas en otro sitio, ponlo detrás de HTTPS (los tokens viajan en cabeceras).
- Copia de seguridad: guarda periódicamente el archivo `maybechat.db`.
