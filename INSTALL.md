Installation guide
=============

A terse installation guide.

Directory setup
-----

We'll cover an Apache installation below, but feel free to toss this in whatever web server you prefer.

In your document root, create a directory called librarycloud:

cd /some/path/to/doc_root; mkdir librarycloud

Clone the librarycloud git repo into your newly created directory:

git clone https://github.com/dpla/platform.git librarycloud.v.01

Copy the example .htaccess to the parent librarycloud directory:

cp librarycloud.v.01/.htaccess.example ./.htaccess


Load your libraries
-----

We'll need the memcached library: http://pecl.php.net/package/memcached

wget http://launchpad.net/libmemcached/1.0/0.52/+download/libmemcached-0.52.tar.gz
tar xvfz libmemcached-0.52.tar.gz
cd libmemcached-0.52
./configure
./make
./make install
sudo pecl install memcached
sudo echo "extension=memcached.so" > /etc/php.d/memcached.ini
sudo apachectl restart


We'll also need the solr-php-client library found at http://code.google.com/p/solr-php-client/

cd librarycloud/librarycloud.v.01/library;
wget http://solr-php-client.googlecode.com/files/SolrPhpClient.r60.2011-05-04.zip;
unzip SolrPhpClient.r60.2011-05-04.zip;


Install supporting services
-----

Install Solr, http://lucene.apache.org/solr/

Install Memcached, http://memcached.org/


Solr setup and load
-----

Copy the librarycloud/librarycloud.v.01/solr/conf/item/schema.xml to your solr core's config

You can load data into Solr using the scripts in librarycloud/librarycloud.v.01/data_load (covering the data load is outside the scope of this install guide)

config file setup


Config
-----

Copy librarycloud/librarycloud.v.01/etc/librarycloud.ini.example to librarycloud/librarycloud.v.01/etc/librarycloud.ini
update librarycloud.ini with proper values

Set the Memcached config in librarycloud/librarycloud.v.01/index.php  Be sure you set the port and host and set Memcached constat to true if using in production.