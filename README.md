# ArshWell | PHP Framework | for LAMP Stack

Simple to learn and use:
>  ArshWell started from the idea of a fast and clean framework. <br>
>  No MVC: less OOP *(at least for now)*.
---

### Tech

ArshWell uses next technologies:

- [MySQL] - Package uses SQL
- [PHP 7.4] - Also some OOP features (ex: DB)
- [SASS] - A good fit CSS extension language for ArshWell
- [JS Vanilla functions] - Built-in helpful functions (ex: Web, Form)
- [jQuery] - default JS library

### Installation & Use

- Include this package (arsavinel/arshwell) in your project composer;
- Run `composer install`;
- Create `index.php`, in root of your project, and include the following code:

    - ```php
      <?php
      /**
       * Used for web requests towards pages.
       */
      require("vendor/arsavinel/arshwell/resources/php/index.php");
      ```
- Create `download.php`, in root of your project, and include the following code:

    - ```php
      <?php
      /**
       * Used for web requests towards uploaded files (png, jpg, gif, etc).
       * So access can be restricted in necessary situations.
       */
      require("vendor/arsavinel/arshwell/resources/php/download.php");
      ```
- Refresh project from web. So necessary .htaccess files will be created automatically.

### Features

ArshWell has many features, including:

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

| Feature |
| ------ |
| Recompiling SCSS/JS files |
| Downloading project as ZIP |
| Updating with newer version (throw ZIP file) |
| Removing dangerous files |
| Activating maintenance mode |

It can only be accessed by developer and provides so many other helpful tools.
Don't believe us, see for yourself.

### Contributing

Thank you for considering contributing to the ArshWell framework!

- Fork the repo, from GitHub
- Run, from terminal, in the root of your project: <br>
  `composer require [your-user]/[your-new-fork] --prefer-source`
    - In that way, you can modify ArshWell directly inside your vendor's project
    - And after that, just `git commit` & `git push` the ArshWell from you vendor
- Come back to GitHub ArshWell and create a Pull Request
    - Explain the problem you've found
    - Present the solution you've implemented;

### Code of Conduct

In order to ensure that the ArshWell community is welcoming to all,
please review and abide by the CODE_OF_CONDUCT.md.

### Security Vulnerabilities

If you’ve found a security issue in ArshWell, please use the procedure
described in [SECURITY](https://github.com/arsavinel/ArshWell/security/policy).

In that situation, please, don't create an issue.

### License

The ArshWell framework is open-sourced software licensed under the MIT license.
