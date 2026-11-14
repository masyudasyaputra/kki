<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicFileTest extends TestCase
{
    public function test_uploaded_photos_are_accessible_without_a_storage_link_on_local_and_https_hosts(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('photo.jpg')->store('member-photos/1', 'public');

        foreach (['http://kki.test', 'https://karate.example'] as $host) {
            $response = $this->get($host.'/storage/'.$path);
            $response->assertOk()->assertHeader('Content-Type', 'image/jpeg')
                ->assertHeader('X-Content-Type-Options', 'nosniff');
            $this->assertSame(realpath(Storage::disk('public')->path($path)), $response->baseResponse->getFile()->getRealPath());
        }
    }

    public function test_public_certificate_images_and_pdfs_are_accessible(): void
    {
        Storage::fake('public');
        $image = UploadedFile::fake()->image('signature.png')->store('certificates/templates', 'public');
        Storage::disk('public')->put('certificates/template.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");
        $this->get('/storage/'.$image)->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get('/storage/certificates/template.pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_missing_private_and_executable_files_are_not_served(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Storage::disk('local')->put('private.jpg', 'secret');
        Storage::disk('public')->put('script.php', '<?php echo "secret";');
        Storage::disk('public')->put('fake.jpg', '<?php echo "secret";');
        foreach (['missing.jpg', 'private.jpg', '../private/private.jpg', 'script.php', 'fake.jpg'] as $path) {
            $this->get('/storage/'.$path)->assertNotFound();
        }
    }
}
