<?php


use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\Trait\SerializesAppleManager;
use Spatie\LaravelData\Data;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

uses(TestCase::class);

class TestSerializableClass
{
    private static ?Serializer $serializer = null;

    protected ?string $sign = null;
    public $publicProp;
    protected $protectedProp;
    private $privateProp;

    public function __construct($public, $protected, $private)
    {
        $this->publicProp    = $public;
        $this->protectedProp = $protected;
        $this->privateProp   = $private;
    }

    public function sign()
    {
        //todo something
    }

    /**
     * @return mixed
     */
    public function getPublicProp()
    {
        return $this->publicProp;
    }

    /**
     * @param mixed $publicProp
     * @return TestSerializableClass
     */
    public function setPublicProp($publicProp)
    {
        $this->publicProp = $publicProp;

        return $this;
    }

    /**
     * @param mixed $protectedProp
     * @return TestSerializableClass
     */
    public function setProtectedProp($protectedProp)
    {
        $this->protectedProp = $protectedProp;

        return $this;
    }

    /**
     * @param mixed $privateProp
     * @return TestSerializableClass
     */
    public function setPrivateProp($privateProp)
    {
        $this->privateProp = $privateProp;

        return $this;
    }

    // Add getter methods for protected and private properties
    public function getProtectedProp()
    {
        return $this->protectedProp;
    }

    public function getPrivateProp()
    {
        return $this->privateProp;
    }

    public static function getSerializer(): Serializer
    {
        if (self::$serializer === null) {
            $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());
            $spatieDataNormalizer = new SpatieDataNormalizer();
            $objectNormalizer     = new ObjectNormalizer($classMetadataFactory, null, null, null, null, null);
            $encoder              = new JsonEncoder();
            self::$serializer     = new Serializer([$spatieDataNormalizer, $objectNormalizer], [$encoder]);
        }

        return self::$serializer;
    }

    public function serialize(): string
    {
        return self::getSerializer()->serialize($this, 'json', [
            'ignored_attributes'                           => ['serializer'],
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return get_class($object);
            },
        ]);
    }

    public static function unserialize(string $serialized): static
    {
        return self::getSerializer()->deserialize($serialized, static::class, 'json', [
            AbstractNormalizer::OBJECT_TO_POPULATE => new static(null, null, null),
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['serializer'],
        ]);
    }
}


class TestSerializable
{
    private $data = 'test';

    public function getData()
    {
        return $this->data;
    }

    public function setData(string $data): TestSerializable
    {
        $this->data = $data;

        return $this;
    }


}

class TestData extends Data
{
    public function __construct(public string $key)
    {
    }
}

class SpatieDataNormalizer implements NormalizerInterface
{
    public function normalize(
        $object,
        string $format = null,
        array $context = []
    ): float|int|bool|ArrayObject|array|string|null {
        if ($object instanceof Data) {
            return $object->toArray();
        }

        return $object;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Data;
    }

    public function supportsDenormalization($data, string $type, string $format = null)
    {
        return is_subclass_of($type, Data::class);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Data::class => true,
        ];
    }
}

test('SerializesAppleManager can serialize and unserialize Data objects', function () {
    $dataObject = new TestData('value');
    $original   = new TestSerializableClass('aaaaaaa', null, null);
    $serialized = $original->serialize();

    $unserialized = TestSerializableClass::unserialize($serialized);
    dd($serialized, $unserialized);

    expect($unserialized->publicProp)
        ->toBeInstanceOf(TestData::class)
        ->and($unserialized->publicProp->toArray())->toBe(['key' => 'value']);
});

test('SerializesAppleManager can handle Serializable objects', function () {
    $serializable = new TestSerializable();
    $original     = new TestSerializableClass($serializable, null, null);
    $serialized   = serialize($original);
    $unserialized = unserialize($serialized);

    expect($unserialized->publicProp)
        ->toBeInstanceOf(TestSerializable::class)
        ->and($unserialized->publicProp->getData())->toBe('test');
});


