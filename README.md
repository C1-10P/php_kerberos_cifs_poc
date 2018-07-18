## Synopsis

Testcode Kerberos Auth CIFS Shares

With this test code I could read from a cifs-share with kerberos sso. I have used a domain-joined Windows 10 client and a Samba 4 AD. Authentication takes place in the background with a Kerberos ticket. Therefore, the user does not have to enter his username and password in the browser window.

## Installation

* AD-Domain: domain.local
* DC: 192.168.1.40
* Testserver: 192.168.1.50
* Testserver-vhost-url: test.domain.local


### testserver: apache kerb auth
```
apt-get install libapache2-mod-auth-kerb
```

### testserver: php wraper smbclient

```
apt-get install php-pear php5-dev libsmbclient libsmbclient-dev 
pecl install smbclient
```
add extension-name to php.ini and restart apache

### testserver: php wrapper krb5
do not know if all libs are necessary. if you find out please give me a hint.
```
apt-get install libcurl3-openssl-dev krb5-dev libgssapi-krb5-2 libgssapi-krb5-2-dev libkrb5-dev
pecl install krb5
```
add extension-name to php.ini and restart apache

### testserver: debug-tool
```
apt-get install smbclient 
```

### testserver: apache config


```
<Location />
                AuthType Kerberos
                AuthName "Kerberos authenticated intranet"
                KrbAuthRealms DOMAIN.LOCAL
                KrbServiceName HTTP/test.domain.local
                Krb5Keytab /etc/test.keytab
                KrbMethodNegotiate On
                KrbMethodK5Passwd On
                KrbSaveCredentials On
                require valid-user
</Location>
```

### dc: keytab creation

create a keytab on dc and transfer to the testserver:

```
samba-tool user add test-service --random-password
samba-tool spn add HTTP/test.domain.local test-service
samba-tool domain exportkeytab test.keytab --principal=HTTP/test.domain.local@domain.local
samba-tool dns add 192.168.1.40 domain.local test A 192.168.1.50
```
transfer keytab to testserver by scp

### client: krb5.conf

```
[libdefaults]
        default_realm = domain.local
[...]
[realms]
        DOMAIN.LOCAL = {
                kdc = 192.168.1.40
                admin_server = 192.168.1.40
        }
        [...]
```

### ad: config kerberos delegation

configure kerberos delegation for the test-service account. for the test i used unconstrained delegation. it should work with constrained delegation as well. you can use samba-tool or the microsoft tool ad user and groups.
* https://www.samba.org/samba/docs/current/man-html/samba-tool.8.html
* https://blogs.msdn.microsoft.com/autz_auth_stuff/2011/05/03/kerberos-delegation/

### client: config browser

in internet options you have to add test.domain.local to the trusted sites.
* https://ping.force.com/Support/PingFederate/Integrations/How-to-configure-supported-browsers-for-Kerberos-NTLM

## Hints
* try to access the cifs share with smbclient on the testserver to test the connection. I had to create some static dns records because my testserver is not domain-joined and uses an other dns-server than the domain.

```
smbclient //domain.local/netlogon -U Administrator
```

* the code does not work on every distribution and php-config.
  * Ubuntu 18.04 (pecl krb5 and pecl smbclient) works
  * Centos OS 7 works with software collections
  
#### Cent OS 7
newer apache and mod_auth_kerb:
```
yum install centos-release-scl
yum install httpd24
yum install httpd24-mod_auth_kerb
systemctl start httpd24-httpd
```
php-fpm with krb5 and smbclient

```
yum install rh-php71 rh-php71-php-fpm rh-php71-php-pear
yum install rh-php71-php-devel rh-php71-devel
yum group install "Development Tools"
yum install libsmbclient-devel
yum install krb5-devel
/opt/rh/rh-php71/root/bin/pecl install krb5
/opt/rh/rh-php71/root/bin/pecl install smbclient
```
* php-fpm config (/etc/opt/rh/rh-php71/php-fpm.d/www.conf) sample: centos7-scl/www.conf
* php-apache-loader (/opt/rh/httpd24/root/etc/httpd/conf.d/php.conf) sample: centos7-scl/php.conf
* php-krb5-ext-loader (/etc/opt/rh/rh-php71/php.d/krb5.ini): centos7-scl/krb5.ini
* php-smbclient-ext-loader (/etc/opt/rh/rh-php71/php.d/smbclient.ini): centos7-scl/smbclient.ini

```
systemctl start rh-php71-php-fpm
```

* maybe: disable selinunx /etc/sysconfig/selinux 
* optional: yum install samba-client

### License

GPL v3


