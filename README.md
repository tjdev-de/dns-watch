# dns-watch
[dns-watch.org](https://www.dns-watch.org) is a service that allows you to see which (mainly German) Internet Service Providers try to censor internet access of their customers.


## Background
The CUII, which is a German institution consisting of the biggest German ISPs and rightsholders, censors parts of the Internet to reduce the number of copyright infringements in the World Wide Web. In order to block these sites, they use the already existing DNS provided by German ISPs. When your device asks your ISP where it can find a website, it will give you wrong information and instead redirect you to the page of CUII.
Using dns-watch.org, you can check which ISPs actively censor parts of the Internet. This tool can also help developers of blocked sites by giving them information on which ISPs are blocking their web services.
We support a Free Web without Internet censorship, and so should you!


## Directory structure
**Because this web page takes advantage of our custom PHP backend, the code may not make sense immediately. For research purposes, here is an outline of the directories.**

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
