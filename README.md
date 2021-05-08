# dns-watch
[dns-watch.org](https://www.dns-watch.org) is a service that allows you to see which (mainly German) Internet Service Providers try to censor internet access of their customers.


## Background
The CUII, which is a German institution consisting of the biggest German ISPs and rightsholders, censors parts of the Internet to reduce the number of copyright infringements in the World Wide Web. In order to block these sites, they use the already existing DNS provided by German ISPs. When your device asks your ISP where it can find a website, it will give you wrong information and instead redirect you to the page of CUII.

Using dns-watch.org, you can check which ISPs actively censor parts of the Internet. This tool can also help developers of blocked sites by giving them information on which ISPs are blocking their web services.

We support a Free Web without Internet censorship, and so should you!


## Other Useful Information

### Directory Structure
**Because this web page takes advantage of our custom PHP backend, the code and especially the functions provided by our backend may not make sense immediately. For research purposes as well as help when you want to contribute to the project (feel free to do so!), here is an outline of the directories and some of the files located inside of them:**

- `/config`: config files for the backend behavior
  - cache.php: lookup caching
  - lookup.php: dns lookups
  - nameserver.php: dns servers to use

- `/functions`: basic callbacks for backend tasks
  - lookup.php: dns lookups and caching

- `/icon`: icons for ISPs and DNS providers

- `/lookup`: page for lookup api with entrypoint for the api

- `/start`: start page ([dns-watch.org](https://dns-watch.org))
