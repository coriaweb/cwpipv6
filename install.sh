#!/bin/bash
\cp -Rf modules/* /usr/local/cwpsrv/htdocs/resources/admin/modules/
\cp -Rf include/* /usr/local/cwpsrv/htdocs/resources/admin/include/
\cp -Rf hooks/ /usr/local/cwpsrv/htdocs/resources/admin/
#Add menu
if ! grep -q "\-- cwp_ipv6 --" /usr/local/cwpsrv/htdocs/resources/admin/include/3rdparty.php
then
cat <<'EOF' >> /usr/local/cwpsrv/htdocs/resources/admin/include/3rdparty.php
<!-- cwp_ipv6 -->
<noscript>
</ul>
<li class="custom-menu"> <!-- this class "custom-menu" was added so you can remove the Developer Menu easily if you want -->
    <a href="?module=icecast"><span class="icon16 icomoon-icon-volume-high"></span>Icecast</a>
</li>
<li style="display:none;"><ul>
</noscript>
<script type="text/javascript">
        $(document).ready(function() {
                var newButtons = ''
                +' <li>'
                +' <a href="?module=icecast" class=""><span aria-hidden="true" class="icon16 icomoon-icon-volume-high"></span>Icecast</a>'
                +'</li>';
                $("li#mn-3").before(newButtons);
        });
</script>
<!-- end cwp_ipv6 -->
EOF
fi
