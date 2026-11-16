# Mi portal — normas de trabajo

Plugin de WordPress (capa de `/mi-cuenta/` de WooCommerce). Repositorio propio: https://github.com/22MW/mi-portal (separado del git del proyecto 22mw). Idioma: español.

## Ramas
- `devMishaPortal`: trabajo. `main`: estable. Se trabaja y se commitea en `devMishaPortal`, se sube, y se pasa a `main` solo cuando el usuario lo pide.
- Un único commit por cambio. Mensajes: `tipo(mi-portal): descripción`. Sin secretos en el código ni en los commits.

## Versión nueva (hacerlo todo, sin pedir pasos manuales al usuario)
1. Subir la versión en **los dos sitios** de `mi-portal.php`: cabecera `Version:` y la constante `MI_PORTAL_VERSION`.
2. Commit en `devMishaPortal` y `git push origin devMishaPortal`.
3. Si el usuario pide pasar a main: `git switch main && git merge --ff-only devMishaPortal`.
4. Etiqueta anotada y push: `git tag -a vX.Y.Z -m "..." && git push origin main vX.Y.Z`.
5. `.github/workflows/release.yml` crea solo la release con `mi-portal.zip` (carpeta `mi-portal/`). **Comprobar que la release tiene el adjunto**. Si el flujo falla, adjuntarlo uno mismo con la API (ver «GitHub»).
6. Zip de respaldo en `~/Downloads`: `git archive --format=zip --prefix=mi-portal/ -o ~/Downloads/mi-portal-X.Y.Z.zip vX.Y.Z`.

## La carpeta del zip NO lleva número de versión
WordPress solo reemplaza un plugin cuando la carpeta del zip se llama igual que la instalada. El «Source code (zip)» de GitHub trae `mi-portal-1.2.2` y crea copias nuevas (pasó en staging de Bodegas Vi Rei: 3 copias a la vez). El zip bueno lleva siempre la carpeta `mi-portal/`. Nunca dar al usuario el zip automático de GitHub.

## GitHub
- No hay `gh`. Usar la API con `GITHUB_TOKEN`, que está en `/Users/22mw/Local Sites/plugins/.env`. Leerlo en memoria; **no copiarlo ni mostrarlo ni commitearlo**.
- Subir un adjunto a una release: `POST https://uploads.github.com/repos/22MW/mi-portal/releases/<id>/assets?name=<archivo>.zip`.

## Qué no hacer
- Pasarle al usuario pasos manuales que se pueden ejecutar desde aquí.
- Proponer un método nuevo sin mirar antes cómo lo hace ya en sus otros plugins (`/Users/22mw/Local Sites/plugins/app/public/wp-content/plugins/`, p. ej. `Woo-Broadcast-Mailer/.github/workflows/release.yml`).
- «Borrar» el plugin desde WordPress: ejecuta `uninstall.php`, que borra `mi_portal_ajustes` y `mi_portal_solo_admin`. Para quitar copias sobrantes, por FTP.
- Tocar `bvr-event-entries` u otros plugins: los fallos se corrigen aquí.

## Traducción
Textos con `__()` y dominio `mi-portal`; plantilla en `languages/mi-portal.pot` (originales en español); regenerar con `xgettext` al añadir textos. Los nombres escritos en el panel (pestañas, grupos) se registran en WPML, contexto «Mi portal (ajustes)».

## Verificación
No hay entorno de pruebas aquí: la comprobación real (QR de «ver pedido», idiomas, menú móvil) la hace el usuario en staging. Decir siempre qué se probó y qué no.
