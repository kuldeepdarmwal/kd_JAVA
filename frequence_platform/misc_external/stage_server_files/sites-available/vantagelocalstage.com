# Place any notes or comments you have here
# It will make any customisation easier to understand in the weeks to come

# domain: domain1.com
# public: /home/demo/public_html/domain1.com/

<VirtualHost *:80>

  # Admin email, Server Name (domain name) and any aliases
  ServerAdmin webmaster@vantagelocal.com
  ServerName  secure.vantagelocalstage.com
  ServerAlias *.vantagelocalstage.com *.brandcdnstage.com wl-test-stage.brandcdn.com

	RewriteEngine on
	RewriteCond %{REQUEST_URI} !^/autoscript
	RewriteCond %{HTTP_HOST} vantagelocal.vantagelocalstage.com$
	RewriteRule ^/(.*) http://frequence.vantagelocalstage.com/$1

	RewriteCond %{REQUEST_URI} !^/autoscript
  	RewriteCond %{HTTP_HOST} vantagelocal.brandcdnstage.com$
  	RewriteRule ^/(.*) http://frequence.brandcdnstage.com/$1

  <Directory />
        Options FollowSymLinks -Indexes
        AllowOverride FileInfo
  </Directory>
  <Directory /var/websites/platform.vantagelocalstage.com/public>
        Options -Indexes FollowSymLinks MultiViews
        AllowOverride FileInfo
        Order allow,deny
        allow from all
  </Directory>

  # Index file and Document Root (where the public files are located)
   DirectoryIndex index.php index.html index.htm
   DocumentRoot /var/websites/platform.vantagelocalstage.com/public

  # Custom log file locations
  LogLevel warn
  ErrorLog  /var/websites/platform.vantagelocalstage.com/log/error.log
  CustomLog /var/websites/platform.vantagelocalstage.com/log/access.log combined

</VirtualHost>

<VirtualHost *:443>

  # Admin email, Server Name (domain name) and any aliases
  ServerAdmin webmaster@vantagelocal.com
  ServerName secure.vantagelocalstage.com
  ServerAlias *.vantagelocalstage.com *.brandcdnstage.com wl-test-stage.brandcdn.com

	RewriteEngine on
	RewriteCond %{REQUEST_URI} !^/autoscript
	RewriteCond %{HTTP_HOST} vantagelocal.vantagelocalstage.com$
	RewriteRule ^/(.*) https://frequence.vatnagelocalstage.com/$1
	
	RewriteCond %{REQUEST_URI} !^/autoscript
  	RewriteCond %{HTTP_HOST} vantagelocal.brandcdnstage.com$
  	RewriteRule ^/(.*) https://frequence.brandcdnstage.com/$1

  <Directory />
        Options FollowSymLinks -Indexes
        AllowOverride FileInfo
  </Directory>
  <Directory /var/websites/platform.vantagelocalstage.com/public>
        Options -Indexes FollowSymLinks MultiViews
        AllowOverride FileInfo
        Order allow,deny
        allow from all
  </Directory>


  # Index file and Document Root (where the public files are located)
   DirectoryIndex index.php index.html index.htm
   DocumentRoot /var/websites/platform.vantagelocalstage.com/public

   SSLEngine ON
   SSLCertificateFile /var/websites/platform.vantagelocalstage.com/ssl/wildcard.brandcdnstage.com.ssl/wildcard.brandcdnstage.com.crt
   SSLCertificateKeyFile /var/websites/platform.vantagelocalstage.com/ssl/wildcard.brandcdnstage.com.ssl/wildcard.brandcdnstage.com.key
   SSLCertificateChainFile /var/websites/platform.vantagelocalstage.com/ssl/wildcard.brandcdnstage.com.ssl/wildcard.brandcdnstage.com.intermediate.ca.crt


  # Custom log file locations
  LogLevel warn
  ErrorLog  /var/websites/platform.vantagelocalstage.com/log/error.log
  CustomLog /var/websites/platform.vantagelocalstage.com/log/access.log combined

</VirtualHost>

