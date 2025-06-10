Thanks:
* [Discuz/DiscuzX](https://gitee.com/Discuz/DiscuzX)
* [MathJax](https://www.mathjax.org/)
* [DocPHT](https://github.com/docpht/docpht)

The following tools are used to render the mathematical diagrams in the forum. I am very grateful to the service providers for providing such a good service.
* [Upmath LaTeX Renderer](https://github.com/parpalak/i.upmath.me)
* [Asymptote Command-Line Interface](https://asymptote.sourceforge.io/doc/Command_002dLine-Interface.html)

## Command-line Installation

To install Discuz! non-interactively, first start the PHP built-in server and MariaDB, then run both database initialization steps:

```bash
php -S 127.0.0.1:8000 >/tmp/php_server.log 2>&1 &
mysqld_safe --datadir=/var/lib/mysql &>/tmp/mysqld.log &

# wait for MariaDB
while ! mysqladmin ping -h 127.0.0.1 --silent; do sleep 1; done

# initialize tables
curl -s "http://127.0.0.1:8000/install/index.php?method=do_db_init&allinfo=<BASE64>"
# import initial data
curl -s "http://127.0.0.1:8000/install/index.php?method=do_db_data_init&allinfo=<BASE64>"
```

The second call to `do_db_data_init` loads `install/data/install_data.sql` into the
`pre_common_setting` table so that values like `domain` are populated correctly.
