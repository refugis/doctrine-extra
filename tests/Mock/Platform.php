<?php declare(strict_types=1);

namespace Refugis\DoctrineExtra\Tests\Mock;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\DateIntervalUnit;
use Doctrine\DBAL\Platforms\Keywords\KeywordList;
use Doctrine\DBAL\Platforms\Keywords\PostgreSQLKeywords;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\PostgreSQLSchemaManager;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\TransactionIsolationLevel;

class Platform extends AbstractPlatform
{
    /**
     * {@inheritdoc}
     */
    public function getBooleanTypeDeclarationSQL(array $column): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getIntegerTypeDeclarationSQL(array $column): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getBigIntTypeDeclarationSQL(array $column): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getSmallIntTypeDeclarationSQL(array $column): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function _getCommonIntegerTypeDeclarationSQL(array $column): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeDoctrineTypeMappings(): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getClobTypeDeclarationSQL(array $column): string
    {
        return 'CLOB';
    }

    /**
     * {@inheritdoc}
     */
    protected function getVarcharTypeDeclarationSQLSnippet($length, $fixed = false): string
    {
        $type = $fixed ? 'CHAR' : 'VARCHAR';
        $length = $length ?: 255;

        return "$type($length)";
    }

    /**
     * {@inheritdoc}
     */
    public function getBlobTypeDeclarationSQL(array $column): string
    {
        return 'DUMMY_BINARY';
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'dummy';
    }

    /**
     * {@inheritdoc}
     */
    protected function getBinaryTypeDeclarationSQLSnippet($length, $fixed = false): string
    {
        return $fixed ? 'DUMMY_BINARY('.($length ?: 255).')' : 'DUMMY_VARBINARY('.($length ?: 255).')';
    }

    public function getCurrentDatabaseExpression(): string
    {
        return 'DUMMY_DATABASE()';
    }

    /**
     * {@inheritdoc}
     */
    public function getLocateExpression($string, $substring, $start = null): string
    {
        return 'LOCATE()';
    }

    /**
     * {@inheritdoc}
     */
    public function getDateDiffExpression($date1, $date2): string
    {
        return 'DATE_DIFF(' . $date1 . ', ' . $date2 . ')';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDateArithmeticIntervalExpression($date, $operator, $interval, $unit): string
    {
        return ($operator === '+' ? 'DATE_ADD' : 'DATE_SUB') . '(' . $date . ',' . $interval . $unit->value . ')';
    }

    public function getAlterTableSQL(TableDiff $diff): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getListViewsSQL($database): string
    {
        return 'SHOW VIEWS';
    }

    /**
     * {@inheritdoc}
     */
    public function getSetTransactionIsolationSQL($level): string
    {
        return '';
    }

    public function getDateTimeTypeDeclarationSQL(array $column): string
    {
        return 'TIMESTAMP';
    }

    public function getDateTypeDeclarationSQL(array $column): string
    {
        return 'DATE';
    }

    public function getTimeTypeDeclarationSQL(array $column): string
    {
        return 'TIME';
    }

    protected function createReservedKeywordsList(): KeywordList
    {
        return new PostgreSQLKeywords();
    }

    public function createSchemaManager(Connection $connection): AbstractSchemaManager
    {
        return new PostgreSQLSchemaManager($connection, $this);
    }
}
