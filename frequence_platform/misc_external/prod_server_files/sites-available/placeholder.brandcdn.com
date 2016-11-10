# Place any notes or comments you have here
# It will make any customisation easier to understand in the weeks to come

# domain: domain1.com
# public: /home/demo/public_html/domain1.com/

<VirtualHost 50.56.183.131:80>

  # Admin email, Server Name (domain name) and any aliases
  ServerAdmin webmaster@vantagelocal.com
  ServerName  placeholder.brandcdn.com
  ServerAlias placeholder.brandcdn.com

  <Directory />
        Options FollowSymLinks -Indexes
        AllowOverride FileInfo
  </Directory>
  <Directory /home/vladmin/placeholder.brandcdn.com>
        Options -Indexes FollowSymLinks MultiViews
        AllowOverride FileInfo
        Order allow,deny
        allow from all
  </Directory>  


  # Index file and Document Root (where the public files are located)
   DirectoryIndex index.php index.html index.htm
   DocumentRoot /home/vladmin/placeholder.brandcdn.com



  # Custom log file locations
  LogLevel warn
  ErrorLog  /home/vladmin/log/error.log
  CustomLog /home/vladmin/log/access.log combined

</VirtualHost>

<VirtualHost 50.56.183.131:443>

  # Admin email, Server Name (domain name) and any aliases
  ServerAdmin webmaster@vantagelocal.com
  ServerName  placeholder.brandcdn.com
  ServerAlias placeholder.brandcdn.com

  <Directory />
        Options FollowSymLinks -Indexes
        AllowOverride FileInfo
  </Directory>
  <Directory /home/vladmin/placeholder.brandcdn.com>
        Options -Indexes FollowSymLinks MultiViews
        AllowOverride FileInfo
        Order allow,deny
        allow from all
  </Directory>


  # Index file and Document Root (where the public files are located)
   DirectoryIndex index.php index.html index.htm
   DocumentRoot /home/vladmin/placeholder.brandcdn.com

  SSLEngine ON
  SSLProtocol All -SSLv2 -SSLv3

  SSLCertificateFile /home/vladmin/ssl/brandcdn/brandcdn.com.crt

  SSLCertificateKeyFile /home/vladmin/ssl/brandcdn/brandcdn.com.key
   
  SSLCertificateChainFile /home/vladmin/ssl/brandcdn/intermediate.crt

  # Custom log file locations
  LogLevel warn
  ErrorLog  /home/vladmin/log/error.log
  CustomLog /home/vladmin/log/access.log combined

</VirtualHost>

