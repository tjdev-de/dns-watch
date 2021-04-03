#! /bin/bash

### compile to regular html and css

# compile pug
pug raw/index.pug -o html
echo "<title>dnswatch</title>
<link rel=\"stylesheet\" href=\"index.css\">
<link rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css2?family=Inter:wght@200;300;400;700&amp;display=swap\">
<link rel=\"shortcut icon\" type=\"image/png\" href=\"favicon.png\">
<meta content=\"text/html;charset=utf-8\" http-equiv=\"Content-Type\">
<meta content=\"utf-8\" http-equiv=\"encoding\">
<script src=\"index.js\"></script>
<script src=\"https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js\"></script>" | cat - html/index.html > temp
mv temp html/index.html
echo "<script src=\"feather.js\"></script>" >> html/index.html

# compile sass
sass raw/index.sass html/index.css

# move js
cp raw/*.js html

# move favicon
cp raw/favicon.png html
