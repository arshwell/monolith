# Arshwell v0.x | PHP Framework | for LAMP Stack

Simple to learn and use:
>  Arshwell started from the idea of a fast and clean framework. <br>
>  No MVC: less OOP *(at least for now)*.
---

### Tech

Arshwell uses next technologies:

- [MySQL] - Package uses SQL
- [PHP 7.4] - Also some OOP features (ex: DB)
- [SASS] - A good fit CSS extension language for Arshwell
- [JS Vanilla functions] - Built-in helpful functions (ex: Web, Form)
- [jQuery] - default JS library

### Installation & Setup

1. From terminal, in the root of your project, run `composer require arshwell/monolith:0.*`
2. After that run `sh vendor/arshwell/monolith/bin/install-arshwell-example.sh`
3. Replace, in entire project, MyTeam\MyProject, with your desired namespace
4. Create the .env.local file
5. Done 📢 run your website!

See more details on https://arshwell.github.io/monolith/docs.html#installation

### Features

Arshwell has many features, including:

| Feature | Detail |
| ------ | ------ |
| Routing | JSON files for routing _(including lg, pagination and params)_ |
| DB objects | Easy to create PHP classes for every MySQL table |
| Layouts | HTML/SCSS/JS layouts for pages |
| Pieces | Reusable HTML/SCSS/JS codes |
| Modules | Easy to create CMS pages |
| Compressing | CSS/JS compressing with minimal resources for every page |

### DevPanel

DevPanel is a built-in panel which has many features, including:

| Feature | |
| ------ | ------ |
| Recompiling SCSS/JS files |
| Downloading project as ZIP |
| Updating with newer version (throw ZIP file) | _(deprecated)_ |
| Removing dangerous files |
| Activating maintenance mode |

It can only be accessed by developer and provides so many other helpful tools.
Don't believe us, see for yourself.

### Contributing

Thank you for considering contributing to the Arshwell framework!

- Fork the repo, from GitHub
- Run, from terminal, in the root of your project: <br>
  `composer require [your-user]/[your-new-fork] --prefer-source`
    - In that way, you can modify Arshwell directly inside your vendor's project
    - And after that, just `git commit` & `git push` the Arshwell from your vendor
- Come back to GitHub Arshwell and create a Pull Request
    - Explain the problem you've found
    - Present the solution you've implemented;

See other details on https://arshwell.github.io/monolith/docs.html#contributing

### Code of Conduct

In order to ensure that the Arshwell community is welcoming to all,
please review and abide by the CODE_OF_CONDUCT.md.

### Security Vulnerabilities

If you’ve found a security issue in Arshwell, please use the procedure
described in [SECURITY](https://github.com/arshwell/monolith/security/policy).

In that situation, please, don't create an issue.

### License

The Arshwell framework is open-sourced software licensed under the MIT license.
