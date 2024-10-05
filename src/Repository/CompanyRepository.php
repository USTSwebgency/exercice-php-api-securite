<?php

namespace App\Repository;

use App\Entity\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;
use App\Entity\UserCompanyRole;


/**
 * @extends ServiceEntityRepository<Company>
 */
class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
    }

  // Retourne la liste des entreprises associées à un utilisateur
    public function findCompaniesByUser(User $user): array
    {
        try {
            return $this->createQueryBuilder('c')
                ->join('c.userCompanyRoles', 'ucr')
                ->where('ucr.user = :user')
                ->setParameter('user', $user)
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            // Gérer l'exception ou la relancer
            throw new \RuntimeException('Erreur lors de la récupération des entreprises pour l\'utilisateur : ' . $e->getMessage());
        }
    }


    //Retourne les détails d'une société demandée par un user meme
    public function findOneByUserAndCompanyId(User $user, int $companyId): Company
    {
        $company = $this->createQueryBuilder('c')
            ->join('c.userCompanyRoles', 'ucr')
            ->where('ucr.user = :user')
            ->andWhere('c.id = :companyId')
            ->setParameter('user', $user)
            ->setParameter('companyId', $companyId)
            ->getQuery()
            ->getOneOrNullResult();
    
        if (!$company) {
            throw new NotFoundHttpException("Société non trouvée pour cet utilisateur.");
        }
    
        return $company;
    }
    
    
    

//    /**
//     * @return Company[] Returns an array of Company objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Company
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
