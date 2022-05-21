<?php

namespace App\Controller;

use App\Entity\Secret;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SecretController extends AbstractController
{
    private $emi;

    public function __construct(EntityManagerInterface $emi)
    {
        $this->emi = $emi;
    }

    #[Route('/secret', name: 'app_secret', methods:['POST'])]
    public function index(Request $request): Response
    {

        $secret = new Secret();
        $data = json_decode($request->getContent(),true);

        try {
            //Get datas from POST met
            $secret->setSecretText($data['secret']);
            //If expireAfterViews field is smaller than 1 response error message 405
            if ($data['expireAfterViews'] < 1) {
                throw new \Throwable();
            }

            $secret->setRemainingViews($data['expireAfterViews']);

            if ($data['expireAfter'] === 0) {
                $secret->setExpiresAt(new \DateTime('9999-12-31')); 
            } else {
                $time = new \DateTime();
                $time -> add(new DateInterval('PT'.$data['expireAfter'].'M'));
                $secret->setExpiresAt($time);
            }
            

            //TEST
            $secret->setCreatedAt(new \DateTime());
            $hash = $secret->getSecretText().$secret->getCreatedAt()->format('Y-m-d H:i:s');
            $hash = md5($hash);
            $secret->setHash($hash);

            //FLUSH into database
            $this->emi->persist($secret);
            $this->emi->flush();
            //Response 
            return $this->json($secret->getWithoutId(),200);
        } catch (\Throwable $th) {
            return $this->json('invalid input',405);
        }
    }

    #[Route('/secret/{hash}', name: 'secret_get', methods:['GET'])]
    public function getSecret(string $hash): Response
    {
        
        
        return $this->json('Secret not found',404);
        // return $this->json([
        //     'hash' => $hash
        // ]);
    }
}
