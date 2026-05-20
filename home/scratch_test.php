<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
try {
    $request = Illuminate\Http\Request::create('/search', 'GET', ['q' => 'test']);
    $response = app(App\Http\Controllers\SearchController::class)->index($request);
    
    // Render the response to catch view errors
    if ($response instanceof \Illuminate\Http\Response) {
        echo "Response is Response\n";
    } elseif ($response instanceof \Illuminate\View\View || method_exists($response, 'render')) {
        $content = $response->render();
        echo "Successfully rendered view. Length: " . strlen($content) . "\n";
    } else {
        echo "Response is of type: " . get_class($response) . "\n";
        echo $response->getContent();
    }
} catch (\Throwable $e) {
    echo $e->getMessage() . "\n" . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString();
}
