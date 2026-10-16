<?php

/**
 * When a SuiteCRM contact is saved, store the iTop person id that shares its email.
 */
class ItopContactLink
{
    public function afterSave($bean, $event, $arguments): void
    {
        if (!empty($GLOBALS['itop_contact_link_running'])) {
            return;
        }
        if (!empty($bean->itop_person_id)) {
            return;
        }
        $email = '';
        if (!empty($bean->email1)) {
            $email = (string) $bean->email1;
        }
        $personId = $this->findPersonId($email, (string) $bean->id);
        if ($personId === '') {
            return;
        }
        $GLOBALS['itop_contact_link_running'] = true;
        $bean->itop_person_id = $personId;
        $bean->save(false);
        $GLOBALS['itop_contact_link_running'] = false;
    }

    private function findPersonId(string $email, string $suiteId): string
    {
        $url = rtrim((string) getenv('ITOP_URL'), '/');
        $user = (string) getenv('ITOP_USER');
        $password = (string) getenv('ITOP_PASSWORD');
        if ($url === '' || $user === '' || $password === '') {
            return '';
        }
        if ($suiteId !== '') {
            $byId = $this->query($url, $user, $password, "SELECT Person WHERE suitecrm_id = '".$this->literal($suiteId)."'");
            if ($byId !== '') {
                return $byId;
            }
        }
        if ($email === '') {
            return '';
        }
        $byEmail = $this->query($url, $user, $password, "SELECT Person WHERE email = '".$this->literal($email)."'");
        if ($byEmail === '') {
            return '';
        }
        $this->updatePerson($url, $user, $password, $byEmail, $suiteId);

        return $byEmail;
    }

    private function query(string $url, string $user, string $password, string $oql): string
    {
        $response = $this->call($url, $user, $password, [
            'operation' => 'core/get',
            'class' => 'Person',
            'key' => $oql,
            'output_fields' => 'id',
        ]);
        $objects = $response['objects'] ?? null;
        if (!is_array($objects) || $objects === []) {
            return '';
        }
        $first = reset($objects);

        return (string) ($first['key'] ?? '');
    }

    private function updatePerson(string $url, string $user, string $password, string $personId, string $suiteId): void
    {
        if ($suiteId === '') {
            return;
        }
        $this->call($url, $user, $password, [
            'operation' => 'core/update',
            'comment' => 'Link SuiteCRM contact',
            'class' => 'Person',
            'key' => $personId,
            'output_fields' => 'id,suitecrm_id',
            'fields' => [
                'suitecrm_id' => $suiteId,
            ],
        ]);
    }

    private function call(string $url, string $user, string $password, array $operation): array
    {
        $curl = curl_init($url.'/webservices/rest.php?version=1.3');
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'auth_user' => $user,
                'auth_pwd' => $password,
                'json_data' => json_encode($operation),
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ]);
        $body = curl_exec($curl);
        curl_close($curl);
        $decoded = is_string($body) ? json_decode($body, true) : null;
        if (!is_array($decoded) || ($decoded['code'] ?? 1) != 0) {
            return [];
        }

        return $decoded;
    }

    private function literal(string $value): string
    {
        return str_replace("'", "\\'", $value);
    }
}
