#! /bin/bash

### make code compatible with jhtml

# compile pug
pug raw/index.pug -o jhtml-compat
mv jhtml-compat/index.html jhtml-compat/index.php
echo "*title(\"dnswatch\")
*icon()
*style(\"///index.css\")
*style(\"https://fonts.googleapis.com/css2?family=Inter:wght@200;300;400;700&amp;display=swap\")
*js(\"///index.js\")
*js(\"https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js\")
*postjs(\"///feather.js\")" | cat - jhtml-compat/index.php > temp
mv temp jhtml-compat/index.php

# compile sass
sass raw/index.sass jhtml-compat/index.css
echo '*stop()' | cat - jhtml-compat/index.css > temp
mv temp jhtml-compat/index.css

# move js
cp raw/*.js jhtml-compat
echo '*stop()' | cat - jhtml-compat/index.js > temp
mv temp jhtml-compat/index.js
