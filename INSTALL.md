Installation guide
=============

A terse installation guide.

Directory setup
-----

We'll cover an Apache installation below, but feel free to toss this in whatever web server you prefer.

* In your document root, create a directory called librarycloud:  
  `cd /some/path/to/doc_root`  
  `mkdir librarycloud`
* Clone the librarycloud git repo into your newly created directory:  
  `git clone https://github.com/dpla/platform.git librarycloud.v.02`
* Copy the example .htaccess to the parent librarycloud directory:  
  `cp librarycloud.v.02/.htaccess.example ./.htaccess`


Load your libraries
-----

We'll need libmemcached (http://libmemcached.org).

````
wget http://launchpad.net/libmemcached/1.0/0.52/+download/libmemcached-0.52.tar.gz
tar xvfz libmemcached-0.52.tar.gz
cd libmemcached-0.52
./configure
./make
./make install
````

We'll also need the php-memcached extension (http://pecl.php.net/package/memcached).

````
pecl install memcached
echo "extension=memcached.so" > /etc/php.d/memcached.ini
````

Restart apache.


Install supporting services
-----

Install Solr, http://lucene.apache.org/solr.

Install Memcached, http://memcached.org.


Solr setup and load
-----

* Copy `librarycloud/librarycloud.v.02/solr/conf/item/schema.xml` to your Solr core's config directory.
* Start your Solr instance.
* Copy `librarycloud/librarycloud.v.02/etc/item_config.ini.example` to `librarycloud/librarycloud.v.02/etc/item_config.ini`
* Update Solr connection details at top of `item_config.ini`.
* You can load data into Solr using the scripts in `librarycloud/librarycloud.v.02/data_load` (covering the data load is outside the scope of this install guide).


Config
-----

* Copy `librarycloud/librarycloud.v.02/etc/librarycloud.ini.example` to `librarycloud/librarycloud.v.02/etc/librarycloud.ini`.
* Update `librarycloud.ini` with proper values.
* Set the Memcached config in `librarycloud/librarycloud.v.02/api/index.php`. Be sure you set the port and host and set `MEMCACHED_ENABLED` to true if using in production.


Access the API
-----

Once you have loaded data into your Solr instance you can query that data via the API:  
http://localhost/librarycloud/librarycloud.v.02/api/item

Read the API documentation for more details:  
http://librarycloud.org/api