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
            <td>No. Surat Jalan/surat kirim/invoice</td>
            <td>
                {{ $no_surat_jalan ?? '-' }}
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
            <td>Certificate of Analysis</td>
            <td>
                @if ($is_coa == 1)
                    (Ada / <s>Tidak ada</s>)
                @else
                    (<s>Ada</s> / Tidak ada)
                @endif
            </td>
            {{-- <td colspan="2"></td> --}}
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
            @if ($nama_barang == null || $nama_barang == '')
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
            @if ($no_batch == null || $no_batch == '')
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
            @if ($expire_date != null || $expire_date != '')
                <td>{{ isset($expire_date) ? (new DateTime($expire_date))->format('d-m-Y') : '-'}}</td>
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
                <td>{{ isset($retest_date) ? (new DateTime($retest_date))->format('d-m-Y') : '-'  }}</td>
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
            <td>Kode Cetak / Potensi</td>

            <td>{{ $kode_cetak }}</td>

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
            @if ($jumlah_terima == null || $jumlah_terima == '')
                <td style="text-align:center">-</td>
            @else
                <td>{{ $jumlah_terima . ' ' . $jumlahterima_um }}</td>
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

            <td style="width: 16%; vertical-align:middle;">{{ $qty_jumlahkemasanluar }}</td>

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

            <td>{{ $qty_jumlahkemasandalam }}</td>

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

            <td>{{ $qty_kondisikemasanluarbaik }}</td>

        </tr>
        <tr>
            <td rowspan="2" style="width: 15%; vertical-align:middle;">JENIS KEMASAN</td>
            <td style="width: 20%; vertical-align:middle;">Luar</td>

            <td>{{ $qty_jeniskemasanluar }}</td>

            <td>Kemasan Luar Tak Baik</td>

            <td>{{ $qty_kondisikemasanluartidakbaik }}</td>

        </tr>
        <tr>
            <td>Dalam</td>

            <td>{{ $qty_jeniskemasandalam }}</td>

            <td>Kemasan Dalam Baik</td>

            <td>{{ $qty_kondisikemasandalambaik }}</td>

        </tr>
        <tr>
            <td rowspan="2" style="width: 15%; vertical-align:middle;">ISI/BERAT</td>
            <td style="width: 20%; vertical-align:middle;">Per Kemasan</td>

            <td>{{ $qty_isiberatperkemasan }}</td>

            <td>Kemasan Dalam Tak Baik</td>

            <td>{{ $qty_kondisikemasandalamtidakbaik }}</td>

        </tr>
        <tr>
            <td>Total Kemasan</td>

            <td>{{ $qty_isiberattotalkemasan }}</td>

            <td colspan="3"></td>
        </tr>
    </table>

    <div class="section-title">III. Tulisan yang Tercantum pada Penandaan</div>
    <table>
        <tr>
            <td width="25%">Nama Barang</td>

            <td>{{ $nama_barang_penanda }}</td>

        </tr>
        <tr>
            <td>Nomor Lot</td>

            <td>{{ $no_batch_penanda }}</td>

        </tr>
        <tr>
            <td>Expiry Date</td>

            <td>{{  $expire_date_penanda}}</td>

        </tr>
        <tr>
            <td>Mfg. Date</td>

            <td>{{ $mfg_date_penanda}}</td>

        </tr>
        <tr>
            <td>Suhu Penyimpanan</td>

            <td>{{ $suhu_penanda ?? '-' }}</td>

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
            <td>Approval</td>
            <td>{{ $approver[0][0][0] ?? '' }}</td>
            <td>{{ $approver[2][0][0] ?? '' }}</td>
            <td>{{ $approver[1][0][0] ?? '' }}</td>
        </tr>
        <tr class="center">
            <td>Nama</td>
            <td>{{ $approver[0][1][0] ?? '' }}</td>
            <td>{{ $approver[2][1][0] ?? '' }}</td>
            <td>{{ $approver[1][1][0] ?? '' }}</td>
        </tr>
        <tr class="center">
            <td>Tanggal</td>
            <td>{{ isset($approver[0][2][0]) ? (new DateTime($approver[0][2][0]))->format('d-m-Y H:i:s') : '-' }}</td>
            <td>{{ isset($approver[2][2][0]) ? (new DateTime($approver[2][2][0]))->format('d-m-Y H:i:s') : '-' }}</td>
            <td>{{ isset($approver[1][2][0]) ? (new DateTime($approver[1][2][0]))->format('d-m-Y H:i:s') : '-' }}</td>
        </tr>
    </table>
</body>

</html>
