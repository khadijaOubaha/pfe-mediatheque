<?php

namespace App\DataFixtures;

use App\Entity\Adherent;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Comptes de démonstration (mots de passe de développement uniquement) :
 *  - admin@mediatheque.test      / admin123     (administrateur)
 *  - biblio@mediatheque.test     / biblio123    (bibliothécaire)
 *  - les 3 premiers adhérents    / adherent123  (adhérents, connexion avec leur email)
 */
class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function getDependencies(): array
    {
        return [AppFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $this->creer($manager, 'admin@mediatheque.test', 'admin123', ['ROLE_ADMIN']);
        $this->creer($manager, 'biblio@mediatheque.test', 'biblio123', ['ROLE_BIBLIOTHECAIRE']);

        $adherents = $manager->getRepository(Adherent::class)->findBy([], ['id' => 'ASC'], 3);
        foreach ($adherents as $adherent) {
            $user = $this->creer($manager, (string) $adherent->getEmail(), 'adherent123', ['ROLE_ADHERENT']);
            $user->setAdherent($adherent);
        }

        $manager->flush();
    }

    /** @param list<string> $roles */
    private function creer(ObjectManager $manager, string $email, string $motDePasse, array $roles): User
    {
        $user = (new User())->setEmail($email)->setRoles($roles);
        $user->setPassword($this->hasher->hashPassword($user, $motDePasse));
        $manager->persist($user);

        return $user;
    }
}
