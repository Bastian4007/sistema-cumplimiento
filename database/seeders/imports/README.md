# Carpeta de staging para cargas masivas de documentos

Esta carpeta es solo para uso local: aquí se descomprime la carpeta de documentos
de un activo (EC/ES/planta/vehículo) antes de correr el seeder de carga masiva
correspondiente. Todo su contenido (excepto este README) está ignorado por git,
así que se puede reutilizar para distintos activos sin ensuciar el repo.

Convención sugerida al descomprimir:

```
database/seeders/imports/
  ES ALLENDE CADEREYTA/
    ... (estructura real de la carpeta original, sin modificar)
```

Usa el nombre del activo (tal como aparece en la tabla `assets`) como carpeta
raíz para que el seeder correspondiente sepa dónde buscar.
