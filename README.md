# dns-watch
[dns-watch.org](https://www.dns-watch.org) is a service that allows you to see which (mainly German) Internet Service Providers try to censor internet access of their customers.



## directory structure
- config/: config files for the backend behavior
  - cache.php: lookup caching
  - lookup.php: dns lookups
  - nameserver.php: dns servers to use

- functions/: basic callbacks for backend tasks
  - lookup.php: dns lookups and caching

- icon/: icons for ISPs and DNS providers

- lookup/: page for lookup-api
  - index.php: entry point for api

- start/: start-page
  - html/: compiled html
  - src/: pug/sass source files
  - feather.js: load feathericons
  - index.css main stylesheet
  - index.js main client scripts
  - index.php entry point for start-page
