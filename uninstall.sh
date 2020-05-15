#!/bin/bash
\rm -f /usr/local/cwpsrv/htdocs/resources/admin/hooks/account/account_remove.php
\rm -f /usr/local/cwpsrv/htdocs/resources/admin/include/ipv6.php
\rm -f /usr/local/cwpsrv/htdocs/resources/admin/modules/ipv6_list.php
\rm -f /usr/local/cwpsrv/htdocs/resources/admin/modules/ipv6.php
\rm -f /usr/local/cwpsrv/htdocs/resources/admin/modules/language/en/ipv6.ini
\rm -f /usr/local/cwpsrv/htdocs/resources/admin/modules/language/es/ipv6.ini
# Remove From Menu
sd=$(grep -n "<\!-- cwp_ipv6 --" /usr/local/cwpsrv/htdocs/resources/admin/include/3rdparty.php | cut -f1 -d:)
ed=$(grep -n "<\!-- end cwp_ipv6 --" /usr/local/cwpsrv/htdocs/resources/admin/include/3rdparty.php | cut -f1 -d:)
cmd="$sd"",""$ed""d"
sed -i.bak -e "$cmd" /usr/local/cwpsrv/htdocs/resources/admin/include/3rdparty.php

