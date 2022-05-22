<?php

namespace App\Controller;

use App\Entity\Secret;
use App\Entity\TestSecret;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Transport\Serialization\Serializer;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer as SerializerSerializer;

class SecretController extends AbstractController
{
    private $emi;
    private $serializer;

    public function __construct(EntityManagerInterface $emi)
    {
        $this->emi = $emi;

        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        $this -> serializer = new SerializerSerializer($normalizers, $encoders);

    }

    #[Route('/secret', name: 'app_secret', methods:['POST'])]
    public function index(Request $request): Response
    {
        $secret = new Secret();

        $testSecret = $this->testSecret($request);

        try {
            //Get datas from POST
            $secret->setSecretText($testSecret->getSecret());
            //If expireAfterViews field is smaller than 1 throw a new Throwable
            if ($testSecret->getExpireAfterViews() < 1) {
                throw new \Throwable();
            }

            $secret->setRemainingViews($testSecret->getExpireAfterViews());

            if ($testSecret->getExpireAfter() === 0) {
                $secret->setExpiresAt(new \DateTime('9999-12-31')); 
            } else {
                $time = new \DateTime();
                $time -> add(new DateInterval('PT'.$testSecret->getExpireAfter().'M'));
                $secret->setExpiresAt($time);
            }
            

            $secret->setCreatedAt(new \DateTime());
            $hash = $secret->getSecretText().$secret->getCreatedAt()->format('Y-m-d H:i:s');
            $hash = md5($hash);
            $secret->setHash($hash);

            //FLUSH into database if any of the required fieldes are NULL it will throw a new Exception 
            $this->emi->persist($secret);
            $this->emi->flush();

            return $this->getResponse($request,$secret);
        } catch (\Throwable $th) {
            return new Response('invalid input',405);
        }
    }

    //ROUTE for the GET method 
    #[Route('/secret/{hash}', name: 'secret_get', methods:['GET'])]
    public function getSecret(string $hash): Response
    {
        try {
            $rep = $this->emi->getRepository(Secret::class);
            $secrets = $rep->findBy(['hash'=>$hash]);
            if (!$secrets) {
                throw new \Throwable();
            }

            $secret = $secrets[0];

            if ($secret->getExpiresAt() < new \DateTime()) {
                $this->deleteSecret($secret);
                throw new \Throwable();
            }

            $secret->setRemainingViews($secret->getRemainingViews()-1);

            if ($secret->getRemainingViews()<1) {
                $this->deleteSecret($secret);
            }

            $this->emi->flush();

            return $this->json($secret->getWithoutId(),200);
        } catch (\Throwable $th) {
            return new Response('Secret not found',404);
        }
    }

    //Assist method for delete a secret.
    public function deleteSecret(Secret $secret): void {
        $this->emi->remove($secret);
        $this->emi->flush();
    }

    public function testSecret(Request $request): TestSecret {
        $data = $request->getContent();

        $testSecret = $this->serializer->deserialize($data, TestSecret::class, $request->getContentType());

        return $testSecret;
    }

    public function getResponse(Request $request, Secret $secret) : Response {
        $data = $this->serializer->serialize($secret->getWithoutId(), $request->getContentType());
        $response = new Response($data,200,['content-type' => $request->getContentType()]);

        return $response;
    }
}
