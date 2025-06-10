# kuing.cjhb.site

This repository contains the source code for the kuing.cjhb.site forum and documentation site. The platform is based on [DiscuzX](https://gitee.com/Discuz/DiscuzX) with [DocPHT](https://github.com/docpht/docpht) used to manage static documentation. Mathematical expressions are rendered with [MathJax](https://www.mathjax.org/), and diagrams are generated through Upmath and Asymptote services.

## Running locally

1. Install PHP (8.0 or newer) and a MySQL-compatible database such as MariaDB.
2. Install composer dependencies:
   ```bash
   composer install
   ```
3. Copy `config/config_global_default.php` to `config/config_global.php` and configure the database connection.
4. Import the database schema from the `install` directory.
5. Launch the site locally:
   ```bash
   php -S localhost:8080
   ```
   Then open `http://localhost:8080` in your browser.

## Acknowledgements

The project uses the following open source tools:
* [Discuz/DiscuzX](https://gitee.com/Discuz/DiscuzX)
* [MathJax](https://www.mathjax.org/)
* [DocPHT](https://github.com/docpht/docpht)

The following services are relied upon for mathematical content:
* [Upmath LaTeX Renderer](https://github.com/parpalak/i.upmath.me)
* [Asymptote Command-Line Interface](https://asymptote.sourceforge.io/doc/Command_002dLine-Interface.html)


