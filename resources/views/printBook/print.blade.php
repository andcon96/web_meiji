<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Checksheet Penerimaan Barang</title>
    <style>
        @page {
            margin: 6mm;
            /* or 1in / 2cm etc. */
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 0px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: top;
        }

        .no-border td,
        .no-border th {
            border: none;
        }

        .center {
            text-align: center;
        }

        .section-title {
            font-weight: bold;
            background: #e6e6e6;
            padding: 3px;
        }

        .sub {
            font-size: 11px;
        }

        .small {
            font-size: 10px;
        }

        .signature-table td {
            height: 40px;
        }

        .checkbox {
            display: inline-block;
            border: 1px solid #000;
            width: 10px;
            height: 10px;
            margin-right: 5px;
        }
    </style>
</head>

<body>
    <h3 style="text-align:center; margin-bottom:5px;">CHECKSHEET PENERIMAAN BARANG</h3>

    <table class="no-border">
        <tr>
            <td>No. Buku Penerimaan Barang: <strong>{{ $no_buku }}</strong></td>
            <td style="text-align:right;">Tanggal datang: <strong>{{ $tanggal }}</strong></td>
        </tr>
    </table>

    <div class="section-title">I. Catatan Kelengkapan Dokumen Penerimaan Barang</div>
    <table>
        <tr>
            <td style="width: 30%">Purchase order</td>
            <td style="width: 20%">
                @if ($is_po == 1)
                    (Sesuai / <s>Tidak Sesuai</s>)
                @else
                    (<s>Sesuai</s> / Tidak Sesuai)
                @endif
            </td>
            <td style="width: 30%">Material Safety Data Sheet (MSDS)</td>
            <td style="width: 20%">
                @if ($is_msds == 1)
                    (Ada / <s>Tidak ada</s>)
                @else
                    (<s>Ada</s> / Tidak ada)
                @endif
            </td>
        </tr>
        <tr>
            <td>Surat Jalan/surat kirim/invoice</td>
            <td>
                @if ($is_sj == 1)
                    (Ada / <s>Tidak ada</s>)
                @else
                    (<s>Ada</s> / Tidak ada)
                @endif
            </td>
            <td>Certificate of Analysis</td>
            <td>
                @if ($is_coa == 1)
                    (Ada / <s>Tidak ada</s>)
                @else
                    (<s>Ada</s> / Tidak ada)
                @endif
            </td>
        </tr>
        <tr>
            <td>Daftar barang/Packing List</td>
            <td>
                @if ($is_packing_list == 1)
                    (Ada / <s>Tidak ada</s>)
                @else
                    (<s>Ada</s> / Tidak ada)
                @endif
            </td>
            <td colspan="2"></td>
        </tr>
    </table>

    <div class="section-title">II. Catatan Pemeriksaan Penerimaan Barang</div>
    <table>
        <tr class="center">
            <th style="width:14%; vertical-align:middle;">Uraian</th>
            <th style="width:40%; vertical-align:middle;">Isi</th>
            <th style="width:22%; vertical-align:middle;">Kesesuaian antara fisik barang dengan dokumen pengiriman</th>
            <th style="width:24%; vertical-align:middle;">Catatan</th>
        </tr>
        <tr>
            <td>Nama Barang</td>
            @if( $nama_barang == null || $nama_barang == '' )
                <td style="text-align:center">-</td>
            @else
            <td>{{ $nama_barang }}</td>
            @endif

            @if ($note_namabarang == '')
                <td>
                    (Sesuai / <s>Tidak Sesuai</s>)
                </td>
                <td style="text-align:center">{{ '-' }}</td>
            @else
                <td>
                    (<s>Sesuai</s> / Tidak Sesuai)
                </td>
                <td>{{ $note_namabarang }}</td>
            @endif

        </tr>
        <tr>
            <td>No. Batch/No. Lot</td>
            @if( $no_batch == null || $no_batch == '' )
                <td style="text-align:center">-</td>
            @else
            <td>{{ $no_batch }}</td>
            @endif

            @if ($note_batch == '')
                <td>
                    (Sesuai / <s>Tidak Sesuai</s>)
                </td>
                <td style="text-align:center">{{ '-' }}</td>
            @else
                <td>
                    (<s>Sesuai</s> / Tidak Sesuai)
                </td>
                <td>{{ $note_batch }}</td>
            @endif


        </tr>
        <tr>
            <td>Expire Date</td>
             @if ($retest_date != null || $retest_date != '')
             <td>{{ $expire_date }}</td>
             @else
                <td style="text-align:center">-</td>
            @endif
            

            @if ($note_expdate == '')
                <td>
                    (Sesuai / <s>Tidak Sesuai</s>)
                </td>
                <td style="text-align:center">{{ '-' }}</td>
            @else
                <td>
                    (<s>Sesuai</s> / Tidak Sesuai)
                </td>
                <td>{{ $note_expdate }}</td>
            @endif

        </tr>
        <tr>
            <td>Re-Test Date</td>
            @if ($retest_date != null || $retest_date != '')
                <td>{{ $retest_date }}</td>
            @else
                <td style="text-align:center">-</td>
            @endif
            

            @if ($note_retestdate == '')
                <td>
                    (Sesuai / <s>Tidak Sesuai</s>)
                </td>
                <td style="text-align:center">{{ '-' }}</td>
            @else
                <td>
                    (<s>Sesuai</s> / Tidak Sesuai)
                </td>
                <td>{{ $note_retestdate }}</td>
            @endif

        </tr>
        <tr>
            <td>Kode Cetak</td>
            @if( $kode_cetak == null || $kode_cetak == '' )
                <td style="text-align:center">-</td>
            @else
            <td>{{ $kode_cetak }}</td>
            @endif

            @if ($note_kodecetak == '')
                <td>
                    (Sesuai / <s>Tidak Sesuai</s>)
                </td>
                <td style="text-align:center">{{ '-' }}</td>
            @else
                <td>
                    (<s>Sesuai</s> / Tidak Sesuai)
                </td>
                <td>{{ $note_kodecetak }}</td>
            @endif

        </tr>
        <tr>
            <td>Jumlah Terima</td>
            @if( $jumlah_terima == null || $jumlah_terima == '' )
                <td style="text-align:center">-</td>
            @else
            <td>{{ $jumlah_terima }}</td>
            @endif

            @if ($note_jumlahterima == '')
                <td>
                    (Sesuai / <s>Tidak Sesuai</s>)
                </td>
                <td style="text-align:center">{{ '-' }}</td>
            @else
                <td>
                    (<s>Sesuai</s> / Tidak Sesuai)
                </td>
                <td>{{ $note_jumlahterima }}</td>
            @endif

        </tr>
    </table>

    <table style="width: 100%">
        <tr>
            <td rowspan="3" style="width: 15%; vertical-align:middle;">SUPPLIER LIST</td>
            <td style="width: 20%; vertical-align:middle;">Pabrik Pembuat</td>
            <td style="width: 24%; vertical-align:middle;">
                @if ($is_pabrikpembuat == 1)
                    (Sesuai / <s>Tidak Sesuai</s>)
                @else
                    (<s>Sesuai</s> / Tidak Sesuai)
                @endif
            </td>
            <td rowspan="2" style="width: 15%; vertical-align:middle;">JUMLAH KEMASAN</td>
            <td style="width: 28%; vertical-align:middle;">Kemasan Luar</td>
            @if( $qty_jumlahkemasanluar == null || $qty_jumlahkemasanluar == '' )
                <td style="width: 16%; vertical-align:middle;text-align:center">-</td>
            @else
            <td style="width: 16%; vertical-align:middle;">{{ $qty_jumlahkemasanluar }}</td>
            @endif
        </tr>
        <tr>
            <td>Alamat Pembuat </td>
            <td>
                @if ($is_alamatpembuat == 1)
                    (Sesuai / <s>Tidak Sesuai</s>)
                @else
                    (<s>Sesuai</s> / Tidak Sesuai)
                @endif
            </td>
            <td>Kemasan Dalam</td>
            @if( $qty_jumlahkemasandalam == null || $qty_jumlahkemasandalam == '' )
                <td style="text-align:center">-</td>
            @else
            <td>{{ $qty_jumlahkemasandalam }}</td>
            @endif
        </tr>
        <tr>
            <td>Pemasok / Agent </td>
            <td>
                @if ($is_agenpemasok == 1)
                    (Sesuai / <s>Tidak Sesuai</s>)
                @else
                    (<s>Sesuai</s> / Tidak Sesuai)
                @endif
            </td>
            <td rowspan="4">KONDISI & JUMLAH</td>
            <td>Kemasan Luar Baik</td>
            @if( $qty_kondisikemasanluarbaik == null || $qty_kondisikemasanluarbaik == '' )
                <td style="text-align:center">-</td>
            @else
            <td>{{ $qty_kondisikemasanluarbaik }}</td>
            @endif
        </tr>
        <tr>
            <td rowspan="2" style="width: 15%; vertical-align:middle;">JENIS KEMASAN</td>
            <td style="width: 20%; vertical-align:middle;">Luar</td>
            @if( $qty_jeniskemasanluar == null || $qty_jeniskemasanluar == '' )
                <td style="text-align:center">-</td>
            @else
            <td>{{ $qty_jeniskemasanluar }}</td>
            @endif
            <td>Kemasan Luar Tak Baik</td>
            @if( $qty_kondisikemasanluartidakbaik == null || $qty_kondisikemasanluartidakbaik == '' )
                <td style="text-align:center">-</td>
            @else
            <td>{{ $qty_kondisikemasanluartidakbaik }}</td>
            @endif
        </tr>
        <tr>
            <td>Dalam</td>
            @if( $qty_jeniskemasandalam == null || $qty_jeniskemasandalam == '' )
                <td style="text-align:center">-</td>
            @else
            <td>{{ $qty_jeniskemasandalam }}</td>
            @endif
            <td>Kemasan Dalam Baik</td>
            @if( $qty_kondisikemasandalambaik == null || $qty_kondisikemasandalambaik == '' )
                <td style="text-align:center">-</td>
            @else
            <td>{{ $qty_kondisikemasandalambaik }}</td>
            @endif
        </tr>
        <tr>
            <td rowspan="2" style="width: 15%; vertical-align:middle;">ISI/BERAT</td>
            <td style="width: 20%; vertical-align:middle;">Per Kemasan</td>
            @if( $qty_isiberatperkemasan == null || $qty_isiberatperkemasan == '' )
                <td style="text-align:center">-</td>
            @else
            <td>{{ $qty_isiberatperkemasan }}</td>
            @endif
            <td>Kemasan Dalam Tak Baik</td>
            @if( $qty_kondisikemasandalamtidakbaik == null || $qty_kondisikemasandalamtidakbaik == '' )
                <td style="text-align:center">-</td>
            @else
            <td>{{ $qty_kondisikemasandalamtidakbaik }}</td>
            @endif
        </tr>
        <tr>
            <td>Total Kemasan</td>
            @if( $qty_isiberattotalkemasan == null || $qty_isiberattotalkemasan == '' )
                <td style="text-align:center">-</td>
            @else
            <td>{{ $qty_isiberattotalkemasan }}</td>
            @endif
            <td colspan="3"></td>
        </tr>
    </table>

    <div class="section-title">III. Tulisan yang Tercantum pada Penandaan</div>
    <table>
        <tr>
            <td width="25%">Nama Barang</td>
            @if( $nama_barang_penanda == null || $nama_barang_penanda == '' )
                <td style="text-align:center">-</td>
            @else
            <td>{{ $nama_barang_penanda }}</td>
            @endif
        </tr>
        <tr>
            <td>Nomor Lot</td>
            @if( $no_batch_penanda == null || $no_batch_penanda == '' )
                <td style="text-align:center">-</td>
                @else
            <td>{{ $no_batch_penanda }}</td>
            @endif
        </tr>
        <tr>
            <td>Expiry Date</td>
            @if( $expire_date_penanda == null || $expire_date_penanda == '' )
                <td style="text-align:center">-</td>
                @else
            <td>{{ $expire_date_penanda }}</td>
            @endif
        </tr>
        <tr>
            <td>Mfg. Date</td>
            @if( $mfg_date_penanda == null || $mfg_date_penanda == '' )
                <td style="text-align:center">-</td>
                @else
            <td>{{ $mfg_date_penanda ?? '-' }}</td>
            @endif
        </tr>
        <tr>
            <td>Suhu Penyimpanan</td>
            @if( $suhu_penanda == null || $suhu_penanda == '' )
                <td style="text-align:center">-</td>
                @else
            <td>{{ $suhu_penanda ?? '-' }}</td>
            @endif
        </tr>
    </table>

    <div class="section-title">IV. Kondisi Kendaraan Pengangkut</div>
    <table>
        <tr>
            <td>
                <label>
                    <input type="checkbox" {{ $kendaraan_is_bersih == 1 ? 'checked' : '' }}
                        style="vertical-align: middle; margin-right: 5px;">
                    <span style="vertical-align: middle;">Bersih</span>
                </label>
            </td>
            <td>
                <label>
                    <input type="checkbox" {{ $kendaraan_is_tidak_bersih == 1 ? 'checked' : '' }}
                        style="vertical-align: middle; margin-right: 5px;">
                    <span style="vertical-align: middle;">Tidak Bersih</span>
                </label>
            </td>
            <td>
                <label>
                    <input type="checkbox" {{ $kendaraan_is_serangga == 1 ? 'checked' : '' }}
                        style="vertical-align: middle; margin-right: 5px;">
                    <span style="vertical-align: middle;">Ada Serangga</span>
                </label>
            </td>
        </tr>
    </table>

    <div class="section-title" style="page-break-before: always;">V. Keterangan</div>
    <table>
        <tr>
            <td style="height:60px;">{{ $rd_keterangan_tambahan }}</td>
        </tr>
    </table>

    <div class="section-title">VI. Pemeriksa</div>
    <table class="signature-table">
        <tr class="center">
            <th>Uraian</th>
            <th>Pemeriksa</th>
            <th>Kepala Grup</th>
            <th>Seksi</th>
        </tr>
        <tr class="center">
            <td>Paraf</td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr class="center">
            <td>Nama</td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr class="center">
            <td>Tanggal</td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    </table>
</body>

</html>
