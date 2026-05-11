<?php
/**
 * ModelPorter — Export and import trained models as portable files
 *
 * Export: creates a self-contained .yuga file (gzipped JSON bundle)
 * Import: loads a .yuga file onto any server, any model name
 *
 * The .yuga format includes:
 *   - Model weights (YugaGen or Brain transformer)
 *   - Tokenizer / vocab
 *   - BM25 retrieval index
 *   - Training metadata
 *   - Version info
 */
class ModelPorter {

    private ModelStore $store;
    private string     $data_dir;

    const FORMAT_VERSION = '1.0';
    const EXTENSION      = '.yuga';

    public function __construct(string $data_dir, ModelStore $store) {
        $this->data_dir = $data_dir;
        $this->store    = $store;
    }

    // ── Export a Brain model to .yuga file ───────────────────────────
    public function export(string $model_name, string $output_path = ''): string {
        $data = $this->store->loadModel('brain_' . $model_name);
        if (!$data) {
            // Try plain model
            $data = $this->store->loadModel($model_name);
        }

        if (!$data) {
            throw new RuntimeException("Model '$model_name' not found.");
        }

        $meta = $this->store->loadMeta($model_name);

        $bundle = [
            'format'     => self::FORMAT_VERSION,
            'exported_at'=> time(),
            'model_name' => $model_name,
            'data'       => $data,
            'meta'       => $meta,
            'checksum'   => md5(json_encode($data)),
        ];

        // Also include YugaGen checkpoint if present
        $ckpt_file = $this->data_dir . "/ckpt_{$model_name}.json.gz";
        if (file_exists($ckpt_file)) {
            $bundle['nanogpt'] = base64_encode(file_get_contents($ckpt_file));
        }

        $compressed = gzencode(json_encode($bundle), 9);

        if (!$output_path) {
            $output_path = $this->data_dir . "/{$model_name}_export_" . date('Ymd_His') . self::EXTENSION;
        }

        file_put_contents($output_path, $compressed);

        return $output_path;
    }

    // ── Import a .yuga file ───────────────────────────────────────────
    public function import(string $file_path, string $new_name = ''): array {
        if (!file_exists($file_path)) {
            throw new RuntimeException("File not found: $file_path");
        }

        $raw    = file_get_contents($file_path);
        $bundle = json_decode(gzdecode($raw), true);

        if (!$bundle || ($bundle['format'] ?? '') !== self::FORMAT_VERSION) {
            throw new RuntimeException("Invalid or unsupported .yuga file format.");
        }

        // Verify checksum
        $expected = $bundle['checksum'] ?? '';
        $actual   = md5(json_encode($bundle['data']));
        if ($expected && $expected !== $actual) {
            throw new RuntimeException("Checksum mismatch — file may be corrupted.");
        }

        $target = $new_name ?: $bundle['model_name'];
        $target = preg_replace('/[^a-z0-9_-]/', '', strtolower($target));

        // Import Brain model
        if (isset($bundle['data'])) {
            $this->store->saveModel('brain_' . $target, $bundle['data']);
        }

        // Import meta
        if (isset($bundle['meta'])) {
            $this->store->saveMeta($target, $bundle['meta']);
        }

        // Import YugaGen checkpoint
        if (isset($bundle['nanogpt'])) {
            $ckpt_file = $this->data_dir . "/ckpt_{$target}.json.gz";
            file_put_contents($ckpt_file, base64_decode($bundle['nanogpt']));
        }

        return [
            'model'       => $target,
            'source_name' => $bundle['model_name'],
            'exported_at' => date('Y-m-d H:i', $bundle['exported_at']),
            'has_nanogpt' => isset($bundle['nanogpt']),
        ];
    }

    // ── Stream export to browser (download) ───────────────────────────
    public function download(string $model_name): void {
        $path = $this->export($model_name);
        $filename = basename($path);

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-cache');

        readfile($path);
        unlink($path); // clean up temp file
        exit;
    }

    // ── List exported files ────────────────────────────────────────────
    public function listExports(): array {
        $files = glob($this->data_dir . '/*' . self::EXTENSION) ?: [];
        $out   = [];
        foreach ($files as $f) {
            $raw    = file_get_contents($f);
            $bundle = json_decode(gzdecode($raw), true);
            $out[]  = [
                'file'        => basename($f),
                'path'        => $f,
                'model_name'  => $bundle['model_name'] ?? 'unknown',
                'exported_at' => date('Y-m-d H:i', $bundle['exported_at'] ?? 0),
                'size_kb'     => round(filesize($f) / 1024),
            ];
        }
        return $out;
    }
}
