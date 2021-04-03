#! /bin/bash

### compile to regular html and css

# compile pug
pug raw/index.pug -o html
echo "<title>dnswatch</title>
<link rel=\"stylesheet\" href=\"index.css\">
<link rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css2?family=Inter:wght@200;300;400;700&amp;display=swap\">
<script src=\"index.js\"></script>
<script src=\"https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js\"></script>" | cat - html/index.html > temp
mv temp html/index.html
echo "<scipt src=\"feather.js\"></script>" >> html/index.html

# compile sass
sass raw/index.sass html/index.css

# move js
cp raw/*.js html
