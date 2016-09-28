<?php

namespace FL\QBJSParserBundle\Service;

use FL\QBJSParser\Parsed\Doctrine\ParsedRuleGroup;
use FL\QBJSParser\Parser\Doctrine\DoctrineParser;
use FL\QBJSParser\Serializer\JsonDeserializer;

class QBJSDoctrineParserService
{
    /**
     * @var array
     * Class Name is the $className argument used when constructing @see DoctrineParser
     * PropertiesMapping is the $queryBuilderFieldsToEntityProperties when constructing @see DoctrineParser
     */
    private $classNameToPropertiesMapping = [];

    /**
     * @var array
     * Class Name is the $className argument used when constructing @see DoctrineParser
     * AssociationMapping is the $queryBuilderFieldPrefixesToAssociationClasses when constructing @see DoctrineParser
     */
    private $classNameToAssociationMapping = [];

    /**
     * @var DoctrineParser[]
     */
    private $classNameToDoctrineParser = [];

    /**
     * @var JsonDeserializer
     */
    private $jsonDeserializer;

    /**
     * @param array $classesAndMappings
     * @param JsonDeserializer $jsonDeserializer
     */
    public function __construct(array $classesAndMappings, JsonDeserializer $jsonDeserializer)
    {
        foreach ($classesAndMappings as $classAndMappings) {
            foreach ($classAndMappings['properties'] as $queryBuilderField => $entityProperty) {
                $this->classNameToPropertiesMapping[$classAndMappings['class']][$queryBuilderField] = $entityProperty ? $entityProperty : $queryBuilderField;
            }
            foreach ($classAndMappings['association_classes'] as $queryBuilderFieldPrefix => $associationClass) {
                $this->classNameToAssociationMapping[$classAndMappings['class']][$queryBuilderFieldPrefix] = $associationClass;
            }
        }
        foreach ($this->classNameToPropertiesMapping as $className => $queryBuilderFieldsToEntityProperties) {
            if(!array_key_exists($className, $this->classNameToAssociationMapping)){
                $this->classNameToDoctrineParser[$className] = new DoctrineParser($className, $queryBuilderFieldsToEntityProperties, []);
            }
            else{
                $this->classNameToDoctrineParser[$className] = new DoctrineParser($className, $queryBuilderFieldsToEntityProperties, $this->classNameToAssociationMapping[$className]);
            }
        }

        $this->jsonDeserializer = $jsonDeserializer;
    }

    /**
     * @param string $jsonString
     * @param string $entityClassName
     * @return ParsedRuleGroup
     */
    public function parseJsonString(string $jsonString, string $entityClassName) : ParsedRuleGroup
    {
        $doctrineParser = $this->newParser($entityClassName);
        return $doctrineParser->parse($this->jsonDeserializer->deserialize($jsonString));
    }


    /**
     * @param string $className
     * @return DoctrineParser
     * @throws \DomainException
     */
    private function newParser(string $className)
    {
        if (!array_key_exists($className, $this->classNameToDoctrineParser)) {
            throw new \DomainException(sprintf(
                'You have requested a Doctrine Parser for %s, but you have not defined a mapping for it in your configuration',
                $className
            ));
        }
        return $this->classNameToDoctrineParser[$className];
    }
}
