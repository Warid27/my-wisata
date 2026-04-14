<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Check if admin is logged in
if (!is_admin()) {
    die('Access denied');
}

// Get parameters
$type = $_GET['type'] ?? 'sales';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Generate PDF based on type
if ($type === 'sales') {
    // Get sales data
    $sales_report = get_sales_report($date_from, $date_to);
    
    // Calculate totals
    $total_orders = 0;
    $total_revenue = 0;
    $total_tickets = 0;
    
    foreach ($sales_report as $sale) {
        $total_orders += $sale['total_orders'];
        $total_revenue += $sale['total_penjualan'];
        $total_tickets += $sale['total_tiket'];
    }
    
    // Create PDF content
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Laporan Penjualan</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
            }
            .header h1 {
                color: #333;
            }
            .info {
                margin-bottom: 20px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            th {
                background-color: #f2f2f2;
                font-weight: bold;
            }
            .total {
                font-weight: bold;
                background-color: #f9f9f9;
            }
            .footer {
                margin-top: 30px;
                text-align: center;
                font-size: 12px;
                color: #666;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>LAPORAN PENJUALAN TIKET EVENT</h1>
            <p>Periode: ' . format_date($date_from) . ' - ' . format_date($date_to) . '</p>
        </div>
        
        <div class="info">
            <p><strong>Total Pesanan:</strong> ' . number_format($total_orders) . '</p>
            <p><strong>Total Tiket Terjual:</strong> ' . number_format($total_tickets) . '</p>
            <p><strong>Total Pendapatan:</strong> ' . format_currency($total_revenue) . '</p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Event</th>
                    <th>Tanggal</th>
                    <th>Pesanan</th>
                    <th>Tiket Terjual</th>
                    <th>Total Penjualan</th>
                </tr>
            </thead>
            <tbody>';
    
    $no = 1;
    foreach ($sales_report as $sale) {
        $html .= '
                <tr>
                    <td>' . $no++ . '</td>
                    <td>' . htmlspecialchars($sale['nama_event']) . '</td>
                    <td>' . format_date($sale['tanggal']) . '</td>
                    <td>' . number_format($sale['total_orders']) . '</td>
                    <td>' . number_format($sale['total_tiket']) . '</td>
                    <td>' . format_currency($sale['total_penjualan']) . '</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
            <tfoot>
                <tr class="total">
                    <td colspan="3">TOTAL</td>
                    <td>' . number_format($total_orders) . '</td>
                    <td>' . number_format($total_tickets) . '</td>
                    <td>' . format_currency($total_revenue) . '</td>
                </tr>
            </tfoot>
        </table>
        
        <div class="footer">
            <p>Laporan ini dihasilkan pada ' . date('d M Y H:i:s') . '</p>
            <p>© ' . date('Y') . ' MyWisata</p>
        </div>
    </body>
    </html>';
    
// Generate PDF using jsPDF (CDN) or simple HTML
    if (true) { // Always use jsPDF from CDN
        // Generate HTML with jsPDF
        echo '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Laporan Penjualan</title>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                }
                .header h1 {
                    color: #333;
                }
                .info {
                    margin-bottom: 20px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                }
                th {
                    background-color: #f2f2f2;
                    font-weight: bold;
                }
                .total {
                    font-weight: bold;
                    background-color: #f9f9f9;
                }
                .footer {
                    margin-top: 30px;
                    text-align: center;
                    font-size: 12px;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>LAPORAN PENJUALAN TIKET EVENT</h1>
                <p>Periode: ' . format_date($date_from) . ' - ' . format_date($date_to) . '</p>
            </div>
            
            <div class="info">
                <p><strong>Total Pesanan:</strong> ' . number_format($total_orders) . '</p>
                <p><strong>Total Tiket Terjual:</strong> ' . number_format($total_tickets) . '</p>
                <p><strong>Total Pendapatan:</strong> ' . format_currency($total_revenue) . '</p>
            </div>
            
            <table id="salesTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Event</th>
                        <th>Tanggal</th>
                        <th>Pesanan</th>
                        <th>Tiket Terjual</th>
                        <th>Total Penjualan</th>
                    </tr>
                </thead>
                <tbody>';
        
        $no = 1;
        foreach ($sales_report as $sale) {
            echo '
                    <tr>
                        <td>' . $no++ . '</td>
                        <td>' . htmlspecialchars($sale['nama_event']) . '</td>
                        <td>' . format_date($sale['tanggal']) . '</td>
                        <td>' . number_format($sale['total_orders']) . '</td>
                        <td>' . number_format($sale['total_tiket']) . '</td>
                        <td>' . format_currency($sale['total_penjualan']) . '</td>
                    </tr>';
        }
        
        echo '
                </tbody>
                <tfoot>
                    <tr class="total">
                        <td colspan="3">TOTAL</td>
                        <td>' . number_format($total_orders) . '</td>
                        <td>' . number_format($total_tickets) . '</td>
                        <td>' . format_currency($total_revenue) . '</td>
                    </tr>
                </tfoot>
            </table>
            
            <div class="text-center mt-4">
                <button onclick="downloadPDF()" class="btn btn-primary">
                    <i class="bi bi-file-earmark-pdf me-2"></i>Download PDF
                </button>
                <button onclick="downloadExcel()" class="btn btn-success ms-2">
                    <i class="bi bi-file-earmark-excel me-2"></i>Download Excel
                </button>
                <button onclick="window.print()" class="btn btn-secondary ms-2">
                    <i class="bi bi-printer me-2"></i>Print
                </button>
            </div>
            
            <div class="footer mt-4">
                <p>Laporan ini dihasilkan pada ' . date('d M Y H:i:s') . '</p>
                <p>© ' . date('Y') . ' MyWisata</p>
            </div>
            
            <script>
                // Convert currency to number
                function currencyToNumber(str) {
                    return parseFloat(str.replace(/[^\d,-]/g, "").replace(",", "."));
                }
                
                // Download PDF using jsPDF
                function downloadPDF() {
                    const { jsPDF } = window.jspdf;
                    const doc = new jsPDF();
                    
                    // Add title
                    doc.setFontSize(16);
                    doc.text("LAPORAN PENJUALAN TIKET EVENT", 105, 15, { align: "center" });
                    doc.setFontSize(10);
                    doc.text("Periode: ' . format_date($date_from) . ' - ' . format_date($date_to) . '", 105, 22, { align: "center" });
                    
                    // Add summary
                    doc.setFontSize(12);
                    doc.text("Total Pesanan: ' . number_format($total_orders) . '", 14, 35);
                    doc.text("Total Tiket Terjual: ' . number_format($total_tickets) . '", 14, 42);
                    doc.text("Total Pendapatan: ' . format_currency($total_revenue) . '", 14, 49);
                    
                    // Add table
                    const tableData = [];
                    const rows = document.querySelectorAll("#salesTable tbody tr");
                    rows.forEach(row => {
                        const rowData = [];
                        row.cells.forEach(cell => {
                            rowData.push(cell.textContent);
                        });
                        tableData.push(rowData);
                    });
                    
                    doc.autoTable({
                        head: [["No", "Event", "Tanggal", "Pesanan", "Tiket Terjual", "Total Penjualan"]],
                        body: tableData,
                        startY: 55,
                        foot: [["TOTAL", "", "", "' . number_format($total_orders) . '", "' . number_format($total_tickets) . '", "' . format_currency($total_revenue) . '"]],
                        footStyles: { fillColor: [240, 240, 240] }
                    });
                    
                    // Save PDF
                    doc.save("Laporan_Penjualan_' . $date_from . '_s_d_' . $date_to . '.pdf");
                }
                
                // Download Excel
                function downloadExcel() {
                    let csv = "No,Event,Tanggal,Pesanan,Tiket Terjual,Total Penjualan\n";
                    
                    const rows = document.querySelectorAll("#salesTable tbody tr");
                    rows.forEach(row => {
                        const rowData = [];
                        row.cells.forEach(cell => {
                            rowData.push("\"" + cell.textContent.replace(/"/g, "\"\"") + "\"");
                        });
                        csv += rowData.join(",") + "\n";
                    });
                    
                    // Add total row
                    csv += "\"TOTAL\",\"\",\"\",\"' . number_format($total_orders) . '\",\"' . number_format($total_tickets) . '\",\"' . format_currency($total_revenue) . '\"\n";
                    
                    // Create download link
                    const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
                    const link = document.createElement("a");
                    const url = URL.createObjectURL(blob);
                    link.setAttribute("href", url);
                    link.setAttribute("download", "Laporan_Penjualan_' . $date_from . '_s_d_' . $date_to . '.csv");
                    link.style.visibility = "hidden";
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            </script>
        </body>
        </html>';
    }
    
} elseif ($type === 'tickets') {
    // Get all sold tickets
    $query = "SELECT a.kode_tiket, a.status_checkin, a.waktu_checkin,
                     u.nama as nama_user, u.email,
                     t.nama_tiket, e.nama_event, e.tanggal
              FROM attendee a
              JOIN order_detail od ON a.id_detail = od.id_detail
              JOIN orders o ON od.id_order = o.id_order AND o.status = 'paid'
              JOIN users u ON o.id_user = u.id_user
              JOIN tiket t ON od.id_tiket = t.id_tiket
              JOIN event e ON t.id_event = e.id_event
              WHERE e.tanggal BETWEEN ? AND ?
              ORDER BY e.tanggal, a.kode_tiket";
    $stmt = $db->prepare($query);
    $stmt->execute([$date_from, $date_to]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Laporan Tiket</title>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 6px;
                text-align: left;
                font-size: 12px;
            }
            th {
                background-color: #f2f2f2;
                font-weight: bold;
            }
            .checked {
                background-color: #d4edda;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>LAPORAN TIKET TERJUAL</h1>
            <p>Periode: ' . format_date($date_from) . ' - ' . format_date($date_to) . '</p>
        </div>
        
        <table id="ticketsTable">
            <thead>
                <tr>
                    <th>Kode Tiket</th>
                    <th>Event</th>
                    <th>Tanggal Event</th>
                    <th>Jenis Tiket</th>
                    <th>Pembeli</th>
                    <th>Email</th>
                    <th>Status Check-in</th>
                    <th>Waktu Check-in</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($tickets as $ticket) {
        $row_class = $ticket['status_checkin'] === 'sudah' ? 'class="checked"' : '';
        $html .= '
                <tr ' . $row_class . '>
                    <td>' . htmlspecialchars($ticket['kode_tiket']) . '</td>
                    <td>' . htmlspecialchars($ticket['nama_event']) . '</td>
                    <td>' . format_date($ticket['tanggal']) . '</td>
                    <td>' . htmlspecialchars($ticket['nama_tiket']) . '</td>
                    <td>' . htmlspecialchars($ticket['nama_user']) . '</td>
                    <td>' . htmlspecialchars($ticket['email']) . '</td>
                    <td>' . ucfirst($ticket['status_checkin']) . '</td>
                    <td>' . ($ticket['waktu_checkin'] ? format_date($ticket['waktu_checkin'], 'd M Y H:i') : '-') . '</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
        
        <div class="text-center mt-4">
            <button onclick="downloadPDF()" class="btn btn-primary">
                <i class="bi bi-file-earmark-pdf me-2"></i>Download PDF
            </button>
            <button onclick="downloadExcel()" class="btn btn-success ms-2">
                <i class="bi bi-file-earmark-excel me-2"></i>Download Excel
            </button>
            <button onclick="window.print()" class="btn btn-secondary ms-2">
                <i class="bi bi-printer me-2"></i>Print
            </button>
        </div>
        
        <div class="footer mt-4">
            <p>Laporan ini dihasilkan pada ' . date('d M Y H:i:s') . '</p>
            <p>© ' . date('Y') . ' MyWisata</p>
        </div>
        
        <script>
            // Download PDF using jsPDF
            function downloadPDF() {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF();
                
                // Add title
                doc.setFontSize(16);
                doc.text("LAPORAN TIKET TERJUAL", 105, 15, { align: "center" });
                doc.setFontSize(10);
                doc.text("Periode: ' . format_date($date_from) . ' - ' . format_date($date_to) . '", 105, 22, { align: "center" });
                
                // Add table
                const tableData = [];
                const rows = document.querySelectorAll("#ticketsTable tbody tr");
                rows.forEach(row => {
                    const rowData = [];
                    row.cells.forEach(cell => {
                        rowData.push(cell.textContent);
                    });
                    tableData.push(rowData);
                });
                
                doc.autoTable({
                    head: [["Kode Tiket", "Event", "Tanggal Event", "Jenis Tiket", "Pembeli", "Email", "Status Check-in", "Waktu Check-in"]],
                    body: tableData,
                    startY: 30,
                    styles: { fontSize: 8 }
                });
                
                // Save PDF
                doc.save("Laporan_Tiket_' . $date_from . '_s_d_' . $date_to . '.pdf");
            }
            
            // Download Excel
            function downloadExcel() {
                let csv = "Kode Tiket,Event,Tanggal Event,Jenis Tiket,Pembeli,Email,Status Check-in,Waktu Check-in\n";
                
                const rows = document.querySelectorAll("#ticketsTable tbody tr");
                rows.forEach(row => {
                    const rowData = [];
                    row.cells.forEach(cell => {
                        rowData.push("\"" + cell.textContent.replace(/"/g, "\"\"") + "\"");
                    });
                    csv += rowData.join(",") + "\n";
                });
                
                // Create download link
                const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
                const link = document.createElement("a");
                const url = URL.createObjectURL(blob);
                link.setAttribute("href", url);
                link.setAttribute("download", "Laporan_Tiket_' . $date_from . '_s_d_' . $date_to . '.csv");
                link.style.visibility = "hidden";
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        </script>
    </body>
    </html>';
    
    echo $html;
}
?>
