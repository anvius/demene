# Generador vendidos DEMENE

## Introducción

Este script permite, con su ejecución, leer los hilos que aparecen en el foro de dominios [demene][1], con las ventas publicadas en español de los últimos años en diferentes plataformas.

## Ejecución

Primero clona el repositorio:

```bash
$> git clone https://github.com/anvius/demene.git
```

Una vez clonado, ya está en el directorio demene y accedemos a él

```bash
$> cd demene
```

Ahora ya se puede ejecutar
```bash
$> php demene.php fichero.csv
```
donde fichero.csv es el nombre del fichero que queremos darle a donde se guardan los datos.

Si se desea que sea ejecutable directamente (siempre con el PHP instalado) se puede, en los casos de Linux y Mac, añadir al principio del fichero:

```php
#!/usr/bin/env php
```
y luego hacer el fichero ejecutable
```bash
$> chmod u+x demene.php
$> mv demene.php demene
```
Con esto podemos ejecutar directamente:
```bash
$> demene fichero_csv
```

[1]: http://www.demene.com
