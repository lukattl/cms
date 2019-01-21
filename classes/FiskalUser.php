<?php

class FiskalUser extends User
{
    private $users = false;
    private $usersCount = NULL;
    private $pps = false;
    private $ppsCount = NULL;

    public function setUsers()
    {
        $sql = "SELECT id_korisnika, ime_korisnika, status_korisnika, prezime_korisnika, oib_korisnika, username_korisnika, uloga FROM korisnik WHERE status_korisnika = 1 AND tvrtka_id = ?";
        $users = $this->db->query($sql, array($this->getData()->tvrtka_id));
        if ($users->getRecords()) {
            $this->usersCount = $users->getRecords();
            $this->users = $users->getResults();
        }
    }

    public function setPps()
    {
        $sql = "SELECT p.id_poslovnog_prostora, p.naziv_poslovnog_prostora, p.adresa_poslovnog_prostora, p.alias_poslovnog_prostora, p.radno_vrijeme_poslovnog_prostora, p.datum_otvaranja_poslovnog_prostora, n.id_naplatnog_uredjaja, n.oznaka_naplatnog_uredjaja, n.broj_racuna_naplatnog_uredjaja, n.printer_naplatnog_uredjaja, n.vrijeme_zadnje_promjene_broja_racuna FROM poslovniprostor p JOIN naplatniuredjaj n ON p.id_poslovnog_prostora = n.poslovni_prostor_id WHERE p.tvrtka_id = ?";
        $pps = $this->db->query($sql, array($this->getData()->tvrtka_id));
        if ($pps->getRecords()) {
            $this->ppsCount = $pps->getRecords();
            $this->pps = $pps->getResults();
        }
    }

    public function imeiExists($imei1, $imei2 = null)
    {
        $sql1 = "SELECT id_mobilnog_uredjaja FROM mobilniuredjaj WHERE imei_1_mobilnog_uredjaja = ? OR imei_2_mobilnog_uredjaja = ? AND status_mobilnog_uredjaja = 1";
        $find1 = $this->db->query($sql1, array($imei1, $imei1));
        if (!$find1->getError() && $find1->getRecords())
            return true;

        if ($imei2) {
            $sql2 = "SELECT id_mobilnog_uredjaja FROM mobilniuredjaj WHERE imei_1_mobilnog_uredjaja = ? OR imei_2_mobilnog_uredjaja = ? AND status_mobilnog_uredjaja = 1";
            $find2 = $this->db->query($sql2, array($imei2, $imei2));
            if (!$find2->getError() && $find2->getRecords()) {
                return true;
            } 
        }
        return false;
    }

    public function insertMobile($mobileData)
    {
        $imei_check = $this->imeiExists($mobileData['imei_1_mobilnog_uredjaja'], $mobileData['imei_2_mobilnog_uredjaja']);
        if (is_numeric($mobileData['imei_1_mobilnog_uredjaja']) && strlen($mobileData['imei_1_mobilnog_uredjaja']) == 15 && !$imei_check) {
            $insertMobile = $this->db->insert('mobilniuredjaj', $mobileData);
            $mob_id = $insertMobile->getLastId();
            if ($mob_id) {
                return true;
            }
        }
        return false;
    }

    public function bpExists($bpLabel, $cdLabel)
    {
        $sql ="SELECT id_poslovnog_prostora from poslovniprostor WHERE naziv_poslovnog_prostora = ? AND tvrtka_id = ?";
        $check = $this->db->query($sql, array($bpLabel, $this->data->tvrtka_id));
        if ($check->getRecords() && !$check->getError()) {
            $sql1 = "SELECT id_naplatnog_uredjaja from naplatniuredjaj WHERE poslovni_prostor_id = ? AND oznaka_naplatnog_uredjaja = ?";
            $check1 = $this->db->query($sql1, array($check->getFirst()->id_poslovnog_prostora, $cdLabel));
            if ($check1->getRecords() && !$check1->getError()) {
                return true;
            }
        }
        return false;
    }

    public function insertBp($data = null)
    {
        if ($data && !$this->bpExists($data['pp'], $data['nu'])) {
            
            $cards = 1;
            if (!isset($data['cards'])) {
                $cards = 0;
            } 
            $bpData = array(
                "naziv_poslovnog_prostora" => $data['pp'],
                "adresa_poslovnog_prostora" => $data['address'],
                "alias_poslovnog_prostora" => $data['alias'],
                "grad_poslovnog_prostora" => $data['city'],
                "postanski_broj_poslovnog_prostora" => $data['post_code'],
                "radno_vrijeme_poslovnog_prostora" => $data['work_time'],
                "tip_poslovnog_prostora" => $data['type'],
                "kartice_poslovnog_prostora" => $cards,
                "tvrtka_id" => $this->data->tvrtka_id
            );
            $insertBp = $this->db->insert('poslovniprostor', $bpData);
            $bp_id = $insertBp->getLastId();

            if ($bp_id) {
                $cdData = array(
                    "oznaka_naplatnog_uredjaja" => $data['nu'],
                    "printer_naplatnog_uredjaja" => $data['printer'],
                    "poslovni_prostor_id" => $bp_id
                );
                $insertCd = $this->db->insert('naplatniuredjaj', $cdData);
                $cd_id = $insertCd->getLastId();

                $mobileData = array(
                    "imei_1_mobilnog_uredjaja" => $data['imei1'],
                    "imei_2_mobilnog_uredjaja" => $data['imei2'],
                    "nu_id" => $cd_id,
                    "tvrtka_id" => $this->data->tvrtka_id
                );

                if ($cd_id) {
                    $insertMobile = $this->insertMobile($mobileData);
                    if($insertMobile) {
                        return true;
                    } else {
                        // IMEI is NOT 15 and NOT numeric and cd_id exists NOT and IMEI exists
                        return false;
                    }
                } 
            } else {
                // bp exists NOT
                return false;
            }
        }
        return false;
    }

    public function deleteUser($id = null)
    {
        if ($id) {
            $update = $this->db->update('korisnik', array("status_korisnika" => 0), array('id_korisnika' => $id));
            if ($update) {
                return true;
            }
        }
        return false;
    }

    public function editUser($data)
    {
        $updateData = array(
            "ime_korisnika" => $data['user_name'],
            "prezime_korisnika" => $data['user_surname'],
            "username_korisnika" => $data['user_username'],
            "uloga" => $data['user_role'],
            "status_korisnika" => 1
        );

        $update = $this->db->update('korisnik', $updateData, array('id_korisnika' => $data['user_id']));
        return $update;
    }

    public function getUsers()
    {
        return $this->users;
    }
    public function countUsers()
    {
        return $this->usersCount;
    }
    public function getPps()
    {
        return $this->pps;
    }
    public function countPps()
    {
        return $this->ppsCount;
    }

}