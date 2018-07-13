## Synopsis

Testcode Kerberos Auth CIFS Shares


## Installation

#apache kerb auth
apt-get install libapache2-mod-auth-kerb

#php wraper smbclient
apt-get install php-pear php5-dev
apt-get install libsmbclient libsmbclient-dev 
pecl install smbclient

#php wrapper krb5
apt-get install libcurl3-openssl-dev krb5-dev
apt-get install libgssapi-krb5-2 libgssapi-krb5-2-dev
apt-get install libkrb5-dev
pecl install krb5

#debug-tool
apt-get install smbclient 


#apache config


<Location />
                AuthType Kerberos
                AuthName "Kerberos authenticated intranet"
                KrbAuthRealms DOMAIN.LOCAL
                KrbServiceName HTTP/server.example.com
                Krb5Keytab /etc/vhost.keytab
                KrbMethodNegotiate On
                KrbMethodK5Passwd On
                KrbSaveCredentials On
                require valid-user
</Location>


# keytab creation

# krb5.conf

## License

GPL V3


