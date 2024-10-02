<?php

namespace App\Repository;

use App\Entity\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    /**
     * Récupère les sociétés associées à un utilisateur via la relation UserCompanyRole
     */
    public function findCompaniesByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.userCompanyRoles', 'ucr')
            ->where('ucr.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

        /**
     * Trouve une société par son ID via UserCompanyRole
     */
    public function findOneByUserAndCompanyId(User $user, int $companyId): ?Company
    {
        return $this->createQueryBuilder('c')
            ->join('c.userCompanyRoles', 'ucr')
            ->where('ucr.user = :user') 
            ->andWhere('c.id = :companyId') 
            ->setParameter('user', $user)
            ->setParameter('companyId', $companyId)
            ->getQuery()
            ->getOneOrNullResult(); 
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
