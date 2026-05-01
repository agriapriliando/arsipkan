<?php

return [
    'required' => ':attribute wajib diisi.',
    'file' => ':attribute tidak valid.',
    'mimetypes' => ':attribute memiliki tipe file yang tidak didukung.',
    'extensions' => ':attribute memiliki format file yang tidak didukung.',
    'max' => [
        'file' => 'Ukuran :attribute terlalu besar. Maksimal 20 MB per file.',
    ],
    'attributes' => [
        'uploadedFile' => 'file',
        'files' => 'file',
        'files.*' => 'file',
    ],
];
