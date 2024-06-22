<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>k-means</title>
    <style>
        /* Reset CSS */
        /* Reset CSS */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 20px;
    background-color: #f9f9f9;
    line-height: 1.6;
}
.container {
    margin: 0 auto;
    padding: 20px;
    background-color: #ffffff;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    border-radius: 10px;
    overflow-x: auto; /* Tambahkan ini untuk mengatasi masalah overflow pada tabel besar */
}
h1, h2 {
    color: #333;
    margin-bottom: 20px;
}
form {
    margin-bottom: 30px;
}
label {
    font-weight: bold;
}
input[type="number"],
input[type="file"],
input[type="submit"] {
    display: block;
    width: 100%;
    padding: 10px;
    margin-top: 10px;
    margin-bottom: 20px;
    border-radius: 5px;
    border: 1px solid #ccc;
}
input[type="submit"] {
    background-color: #007bff;
    color: #fff;
    border: none;
    cursor: pointer;
}
input[type="submit"]:hover {
    background-color: #0056b3;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}
th, td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: center;
}
th {
    background-color: #f2f2f2;
}
.distance-cell {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.distance-value {
    margin-bottom: 5px;
}
.final-table {
    margin-top: 20px;
    border: 1px solid #ddd;
    border-collapse: collapse;
}
.final-table th, .final-table td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: center;
}
.final-table th {
    background-color: #f2f2f2;
}

    </style>
</head>
<body>
    <div class="container">
        <h1>Pengelompokan Hewan Berdasarkan Kingdom</h1>
        <form action="" method="post" enctype="multipart/form-data">
            <label for="clusters">Jumlah Cluster (K):</label>
            <input type="number" id="clusters" name="clusters" min="1" required>
            <br><br>
            <label for="file">Unggah File Excel:</label>
            <input type="file" id="file" name="file" accept=".xlsx,.xls" required>
            <br><br>
            <input type="submit" value="Unggah dan Kelompokkan">
        </form>

        <?php
        require 'vendor/autoload.php';

        use PhpOffice\PhpSpreadsheet\IOFactory;

        function calculate_distances($data, $centroids) {
            foreach ($data as &$point) {
                $point['distances'] = [];
                $point['formulas'] = [];
                foreach ($centroids as $centroid) {
                    $distance = sqrt(pow($point['berat'] - $centroid[0], 2) + pow($point['panjang'] - $centroid[1], 2));
                    $point['distances'][] = $distance;
                    $point['formulas'][] = "√((" . $point['berat'] . " - " . $centroid[0] . ")² + (" . $point['panjang'] . " - " . $centroid[1] . ")²)";
                }
            }
            return $data;
        }

        function update_centroids($clusters) {
            $centroids = [];
            foreach ($clusters as $cluster) {
                $totalBerat = array_sum(array_column($cluster, 'berat'));
                $totalPanjang = array_sum(array_column($cluster, 'panjang'));
                $count = count($cluster);
                $centroids[] = [$totalBerat / $count, $totalPanjang / $count];
            }
            return $centroids;
        }

        function kmeans($data, $k) {
            $centroids = array_map(function($point) {
                return [$point['berat'], $point['panjang']];
            }, array_slice($data, 0, $k));

            $iteration = 0;
            while (true) {
                $iteration++;
                echo "<h2>Iterasi $iteration</h2>";

                echo "<table>
                        <tr>
                            <th>Hewan</th>
                            <th>Berat</th>
                            <th>Panjang</th>";
                for ($i = 0; $i < $k; $i++) {
                    echo "<th style='font-size: 0.8em;'>Rumus: √((" . $centroids[$i][0] . " - berat)² + (" . $centroids[$i][1] . " - panjang)²)</th>";
                    echo "<th>Jarak ke C" . ($i + 1) . " (x, y)</th>";
                }
                echo "<th>Cluster</th>
                    </tr>";

                $data = calculate_distances($data, $centroids);
                $clusters = array_fill(0, $k, []);
                foreach ($data as $point) {
                    $closest = array_keys($point['distances'], min($point['distances']))[0];
                    $clusters[$closest][] = $point;
                    echo "<tr>";
                    echo "<td>{$point['name']}</td>";
                    echo "<td>{$point['berat']}</td>";
                    echo "<td>{$point['panjang']}</td>";
                    foreach ($point['distances'] as $index => $distance) {
                        echo "<td><small>" . $point['formulas'][$index] . "</small></td>";
                        echo "<td>" . number_format($distance, 2) . "</td>";
                    }
                    echo "<td>Cluster " . ($closest + 1) . "</td>";
                    echo "</tr>";
                }
                echo "</table><br>";

                $newCentroids = update_centroids($clusters);

                if ($newCentroids == $centroids) {
                    break;
                }
                $centroids = $newCentroids;

                echo "<strong>Centroid Baru:</strong><br>";
                foreach ($centroids as $index => $centroid) {
                    echo "Centroid " . ($index + 1) . ": Berat: " . number_format($centroid[0], 2) . ", Panjang: " . number_format($centroid[1], 2) . "<br>";
                }
                echo "<br>";
            }

            return $clusters;
        }

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $file = $_FILES['file']['tmp_name'];
            $k = intval($_POST['clusters']);
            
            $spreadsheet = IOFactory::load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $data = [];
            
            foreach ($sheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                $rowData = [];
                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getValue();
                }
                // Asumsi kolom pertama adalah nama, kolom kedua adalah berat, dan kolom ketiga adalah panjang
                $data[] = ['name' => $rowData[0], 'berat' => (float)$rowData[1], 'panjang' => (float)$rowData[2]];
            }
            
            if ($k > count($data)) {
                echo "Jumlah cluster tidak boleh lebih besar dari jumlah data.";
                exit;
            }
            
            $clusters = kmeans($data, $k);

            echo "<h2>Hasil Akhir:</h2>";
            foreach ($clusters as $index => $cluster) {
                echo "<table class='final-table'>";
                echo "<tr><th colspan='3'>Cluster " . ($index + 1) . "</th></tr>";
                echo "<tr><th style='width: 10cm;'>Hewan</th><th style='width: 5cm;'>Berat</th><th style='width: 5cm;'>Panjang</th></tr>";
                foreach ($cluster as $customer) {
                    echo "<tr>";
                    echo "<td style='width: 10cm;'>" . $customer['name'] . "</td>";
                    echo "<td style='width: 5cm;'>" . $customer['berat'] . "</td>";
                    echo "<td style='width: 5cm;'>" . $customer['panjang'] . "</td>";
                    echo "</tr>";
                }
                echo "</table><br>";
                
            }
            echo "<h2>Kesimpulan Pengelompokan:</h2>";
foreach ($clusters as $index => $cluster) {
    $totalBerat = array_sum(array_column($cluster, 'berat'));
    $totalPanjang = array_sum(array_column($cluster, 'panjang'));
    $jumlahAnggota = count($cluster);
    $rataBerat = $totalBerat / $jumlahAnggota;
    $rataPanjang = $totalPanjang / $jumlahAnggota;

    // Tentukan kesimpulan berdasarkan rata-rata berat dan panjang
    $kesimpulan = '';
    if (($rataBerat >= 0.001 && $rataBerat <= 10) && $rataPanjang >= 15 && $rataPanjang <= 20) {
        $kesimpulan = "Annelida (Annelid)";
 } elseif (($rataBerat >= 0.001 && $rataBerat <= 65) && $rataPanjang >= 10 && $rataPanjang <= 15) {
        $kesimpulan = "Amfibi (Amphibia)";
 } elseif (($rataBerat >= 0.001 && $rataBerat <= 10) && $rataPanjang >= 30 && $rataPanjang <= 40) {
        $kesimpulan = "Echinodermata (Echinoderm)";
  } elseif (($rataBerat >= 0.001 && $rataBerat <= 19) && $rataPanjang >= 20 && $rataPanjang <= 25) {
        $kesimpulan = "Artropoda (Arthropoda)";
 } elseif (($rataBerat >= 0.001 && $rataBerat <= 200) && $rataPanjang >= 100 && $rataPanjang <= 120) {
        $kesimpulan = "Moluska (Mollusca)";
  } elseif (($rataBerat >= 0.001 && $rataBerat <= 300) && $rataPanjang >= 15 && $rataPanjang <= 20) {
        $kesimpulan = "Cnidaria (Cnidaria)";
 } elseif (($rataBerat >= 0.002 && $rataBerat <= 45) && $rataPanjang >= 100 && $rataPanjang <= 200) {
        $kesimpulan = "Aves (Burung)";  
   } elseif (($rataBerat >= 0.025 && $rataBerat <= 1000) && $rataPanjang >= 400 && $rataPanjang <= 500) {
        $kesimpulan = "Reptil (Reptilia)";
    } elseif (($rataBerat >= 0.001 && $rataBerat <= 20000) && $rataPanjang >= 0.5 && $rataPanjang <= 600) {
        $kesimpulan = "Ikan (Pisces)";
    } else {
        $kesimpulan = "Terdapat lebih dari 1 kingdom dalam cluster";
    }

    echo "<p><strong>Cluster " . ($index + 1) . ":</strong> Rata-rata Berat = " . number_format($rataBerat, 2) . ", Rata-rata Panjang = " . number_format($rataPanjang, 2) . " -> " . $kesimpulan . "</p>";
}

        }
        ?>
    </div>
</body>
</html>
