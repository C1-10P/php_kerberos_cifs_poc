## Synopsis

Testcode Kerberos Auth CIFS Shares

With this test code I could read from a cifs-share with kerberos sso. I have used a domain-joined Windows 10 client and a Samba 4 AD. Authentication takes place in the background with a Kerberos ticket. Therefore, the user does not have to enter his username and password in the browser window.

## Installation

### apache kerb auth
```
apt-get install libapache2-mod-auth-kerb
```

### php wraper smbclient

```
apt-get install php-pear php5-dev libsmbclient libsmbclient-dev 
pecl install smbclient
```
add extension-name to php.ini and restart apache

### php wrapper krb5
do not know if all libs are necessary. if you find out please give me a hint.
```
apt-get install libcurl3-openssl-dev krb5-dev libgssapi-krb5-2 libgssapi-krb5-2-dev libkrb5-dev
pecl install krb5
```
add extension-name to php.ini and restart apache

### debug-tool
```
apt-get install smbclient 
```

### apache config

* AD-Domain: domain.local
* DC: 192.168.1.40
* Testserver: 192.168.1.50
* Testserver-vhost-url: test.domain.local

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

### keytab creation

create a keytab on dc and transfer to the testserver:

```
samba-tool user add test-service --random-password
samba-tool spn add HTTP/test.domain.local test-service
samba-tool domain exportkeytab test.keytab --principal=HTTP/test.domain.local@domain.local
samba-tool dns add 192.168.1.40 domain.local test A 192.168.1.50
```
transfer keytab to testserver by scp

### krb5.conf

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

## Hints
* try to access the cifs share with smbclient on the testserver to test the connection. I had to create some static dns records because my testserver is not domain-joined and uses an other dns-server than the domain.

```
smbclient //domain.local/netlogon -U Administrator
```

### License

GPL v3


