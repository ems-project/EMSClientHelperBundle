<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Builder;

interface BuilderDocumentInterface
{
    public function getId(): string;

    public function getName(): string;

    public function getContentType(): string;

    public function getDataSource(): array;
}
