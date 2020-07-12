<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Website;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class SitesFixtures extends Fixture
{
    private $encoder;

    const STATUS = [200, 404, 303];
    const SITENAMES = ['facebook' => 'https://www.facebook.com/',
        'google' => 'http://google.com/',
        'youtube' => 'https://www.youtube.com/'];

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }
    public function load(ObjectManager $manager)
    {

        $user = new User();
        $password = $this->encoder->encodePassword($user, 'root');
        $user->setLastname('mez')
            ->setFirstname('nourhene')
            ->setEmail('nourhene.mez@gmail.com')
            ->setPassword($password)
            ->setIsVerified(true);

        $manager->persist($user);

        $user1 = new User();
        $user1->setLastname('mez2')
            ->setFirstname('nour2')
            ->setEmail('mezr@gmail.com')
            ->setPassword($password)
            ->setIsVerified(true);

        $manager->persist($user1);

        $arrayUsers = [$user, $user1];

        foreach ($arrayUsers as $user) {
            foreach (self::SITENAMES as $name => $url) {
                $website = new Website();
                $website->setName($name)
                    ->setStatus(self::STATUS[array_rand(self::STATUS)])
                    ->setUrl($url)
                    ->setUser($user);

                $manager->persist($website);
            }
        }
        $manager->flush();
    }
}
