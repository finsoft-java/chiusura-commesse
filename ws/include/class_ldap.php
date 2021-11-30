<?php

$ldapManager = new LdapManager();

class LdapManager {
    
    /**
     * Login
     */
    function login($username, $pwd) {
		if (AD_FILTER)
			return $this->_common_get_user(AD_SERVER, $username . '@' . AD_DOMAIN, $pwd, $username, AD_BASE_DN, [AD_FILTER => 'validatore', '' => 'codificatore']);
		else
			return $this->_common_get_user(AD_SERVER, $username . '@' . AD_DOMAIN, $pwd, $username, AD_BASE_DN, ['' => 'validatore']);
    }

    /**
     * Si connette a AD con le credenziali date. Cerca lo $username dato e si aspetta di trovare un unico record.
     * $mapFiltersRoles serve per effettuare più ricerche, una per ogni $ruolo desiderato, verrà assegnato il primo che trova.
     * Se non trova nulla dà errore 404.
     */
    function _common_get_user($server, $login_user, $login_pwd, $username, $searchBase, $mapFiltersRoles) {

        $stoFacendoLogin = ($login_user === $username . '@' . AD_DOMAIN);

        $ldap_connection = ldap_connect($server);
        if (FALSE === $ldap_connection) {
            print_error(503, "Errore interno - Impossibile collegarsi al sever Active Directory: " . AD_SERVER);
        }

        // We have to set this option for the version of Active Directory we are using.
        ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.

        $bind = @ldap_bind($ldap_connection, $login_user, $login_pwd);
        if ($bind !== TRUE) {
            if ($stoFacendoLogin) {
                print_error(403, 'Credenziali invalide: ' . $username);
            } else {
                print_error(500, "Errore interno - Impossibile accedere al server Active Directory con l'utenza amministrativa");
            }
        }

        foreach ($mapFiltersRoles as $additionalFilter => $role) {
            if ($additionalFilter) {
                $searchFilter="(&(SamAccountName=$username)$additionalFilter)";
            } else {
                $searchFilter="(SamAccountName=$username)";
            }
            $attributes = array();
            $attributes[] = 'givenname';
            $attributes[] = 'mail';
            $attributes[] = 'samaccountname';
            $attributes[] = 'sn';
            $result = ldap_search($ldap_connection, $searchBase, $searchFilter, $attributes);
            
            if (FALSE === $result) {
                continue;
            }

            $entries = ldap_get_entries($ldap_connection, $result);

            if ($entries['count'] == 0) {
                continue;
            }

            // should be exactly 1
            if ($entries['count'] != 1) {
                @ldap_unbind($ldap_connection); // Clean up after ourselves.
                print_error(500, 'More than 1 user found for username ' . $username);
            }

            $entry = $entries[0];
            $user = (object) [];
            $user->nome_utente = $entry['samaccountname'][0];
            $user->nome = $entry['givenname'][0];
            $user->cognome = $entry['sn'][0];
            $user->email = $entry['mail'][0];
            $user->ruolo = $role;

            @ldap_unbind($ldap_connection); // Clean up after ourselves.
            return $user;
        }

        @ldap_unbind($ldap_connection); // Clean up after ourselves.
        
        if ($stoFacendoLogin) {
            print_error(403, 'Utente non autorizzato: ' . $username);
        } else {
            print_error(403, 'Utente inesistente o non autorizzato: ' . $username);
        }
    }
}
?>