#! /bin/bash

### make code compatible with jhtml

# compile pug
pug ../src/index.pug -o ..
mv ../index.html ../index.php
echo "*title(\"dnswatch\")
*icon()
*style(\"///index.css\")
*style(\"https://fonts.googleapis.com/css2?family=Inter:wght@200;300;400;700&amp;display=swap\")
*js(\"///index.js\")
*js(\"https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js\")
*postjs(\"///feather.js\")" | cat - ../index.php > temp
mv temp ../index.php

# compile sass
sass ../src/index.sass ../index.css --no-source-map
echo '*stop()' | cat - ../index.css > temp
mv temp ../index.css

# move js
cp ../src/*.js ..
echo '*stop()' | cat - ../index.js > temp
mv temp ../index.js
