<?php

namespace App\Services;

use App\Models\PurchaseOrder\POMstr;
use App\Models\SalesOrder\SOMstr;
use App\Models\Settings\Domain;
use App\Models\Settings\qxwsa;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class QxtendServices
{
	private function httpHeader($req)
	{
		return array(
			'Content-type: text/xml;charset="utf-8"',
			'Accept: text/xml',
			'Cache-Control: no-cache',
			'Pragma: no-cache',
			'SOAPAction: ""',        // jika tidak pakai SOAPAction, isinya harus ada tanda petik 2 --> ""
			'Content-length: ' . strlen(preg_replace("/\s+/", " ", $req))
		);
	}

	public function qxSalesOrder($domain_id, $soNumber, $newLine, $action)
	{
		$qxwsa = qxwsa::where('domain_id', $domain_id)->first();
		$domain = Domain::where('id', $domain_id)->first();
		// $salesOrder = SOMstr::with(['getSODetail'])->where('so_nbr', $soNumber)->first();
		$salesOrder = SOMstr::query();
		if (count($newLine) > 0) {
			$salesOrder = $salesOrder->with(['getSODetail' => function ($q) use ($newLine) {
				$q->whereIn('line_detail', $newLine);
			}]);
		} else {
			$salesOrder = $salesOrder->with(['getSODetail']);
		}

		$salesOrder = $salesOrder->where('so_nbr', $soNumber)->first();
		$qxUrl = $qxwsa->qx_url;
		$timeout = 0;
		$receiver = 'risis';

		switch ($action) {
			case 'create':
				// XML Qxtend
				$qdocHead = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>
						<maintainSalesOrder>
						<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>ERP3_2</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>';

				$qdocBody = '<dsSalesOrder>
								<salesOrder>
									<soNbr>' . $salesOrder->so_nbr . '</soNbr>
									<soCust>' . $salesOrder->sold_to . '</soCust>
									<soShip>' . $salesOrder->ship_to . '</soShip>
									<soOrdDate>' . $salesOrder->order_date . '</soOrdDate>
									<soDueDate>' . $salesOrder->need_date . '</soDueDate>
									<soCrTerms>' . $salesOrder->so_ct_code . '</soCrTerms>';


				if ($salesOrder->so_fr_list != '') {
					$qdocBody .= '<soFrList>' . $salesOrder->so_fr_list . '</soFrList>
								<soFrTerms>' . $salesOrder->so_fr_terms . '</soFrTerms>
								<calcFr>true</calcFr>';
				}

				$qdocBody .= '<soCurr>' . $salesOrder->so_currency . '</soCurr>
                    <soTaxc1>' . $salesOrder->so_taxc . '</soTaxc1>';
				if ($salesOrder->so_is_taxable == 'Yes') {
					$qdocBody .= '<soTaxable1>true</soTaxable1>';
				} else {
					$qdocBody .= '<soTaxable1>false</soTaxable1>';
				}

				// if ($salesOrder->so_is_taxin == 'Yes') {
				// 	$qdocBody .= '<taxIn>true</taxIn>';
				// } else {
				// 	$qdocBody .= '<taxIn>false</taxIn>';
				// }

				$qdocBody .= '<soSlspsn>' . $salesOrder->so_sales_person . '</soSlspsn>
										<soRmks>' . $salesOrder->remark . '</soRmks>
										<soPo>' . $salesOrder->so_po . '</soPo>';

				$commentHeader = '';
				if ($salesOrder->comment != '') {
					if (strpos($salesOrder->comment, "\r\n") !== false) {
						$commentHeader = explode("\r\n", $salesOrder->comment);

						$qdocBody .= '<salesOrderTransComment>
						<cmtSeq>1</cmtSeq>
						<cdRef>' . $salesOrder->sold_to . '</cdRef>
						<cdType></cdType>
						<cdLang></cdLang>';

						foreach ($commentHeader as $key => $commentHead) {
							if ($key < 15) {
								$qdocBody .= '<cmtCmmt><![CDATA["' . $commentHead . '"]]></cmtCmmt>';
							}
						}
					} else {
						$commentHeader = $salesOrder->comment;

						$qdocBody .= '<salesOrderTransComment>
						                <cmtSeq>1</cmtSeq>
						                <cdRef>' . $salesOrder->sold_to . '</cdRef>
						                <cdType></cdType>
						                <cdLang></cdLang>';
						$qdocBody .= '<cmtCmmt><![CDATA["' . $commentHeader . '"]]></cmtCmmt>';
					}

					$qdocBody .= '</salesOrderTransComment>';
				}

				foreach ($salesOrder->getSODetail as $soDetail) {
					$qdocBody .= '<salesOrderDetail>
									<line>' . $soDetail->line_detail . '</line>
									<sodPart>' . $soDetail->item_code . '</sodPart>
									<sodQtyOrd>' . $soDetail->qty_order . '</sodQtyOrd>
									<sodListPr>' . $soDetail->list_price . '</sodListPr>
									<discount>' . $soDetail->item_discount . '</discount>
                                    <sodPrice>' . $soDetail->item_net_price . '</sodPrice>
									<sodLoc>' . $soDetail->sod_loc . '</sodLoc>';
					if ($soDetail->sod_is_taxable == 'Yes') {
						$qdocBody .= '<sodTaxable>true</sodTaxable>';
					} else {
						$qdocBody .= '<sodTaxable>false</sodTaxable>';
					}

					if ($soDetail->sod_tax_in == 'Yes') {
						$qdocBody .= '<sodTaxIn>true</sodTaxIn>';
					} else {
						$qdocBody .= '<sodTaxIn>false</sodTaxIn>';
					}
					$qdocBody .= '<sodTaxc>' . $soDetail->sod_taxc . '</sodTaxc>
										<sodUm>' . $soDetail->item_um . '</sodUm>';


					if ($soDetail->comment_det != '') {
						$qdocBody .= '<sodcmmts>true</sodcmmts>';
						$commentDetail = str_split($soDetail->comment_det, 76);
						$qdocBody .= '<salesOrderDetailTransComment>
							<cmtSeq>1</cmtSeq>
							<cdRef>' . $soDetail->item_code . '</cdRef>
							<cdType></cdType>
							<cdLang></cdLang>';

						foreach ($commentDetail as $cmtDet => $cmtDetail) {
							if ($cmtDet < 15) {
								$qdocBody .= '<cmtCmmt>' . $cmtDetail . '</cmtCmmt>';
							}
						}
						$qdocBody .= '</salesOrderDetailTransComment>';
					}

					$qdocBody .= '</salesOrderDetail>';
				}
				$qdocBody .= '<soTrl1Amt>' . $salesOrder->so_trl_amt_1 . '</soTrl1Amt>';
				$qdocBody .= '<soTrl2Amt>' . $salesOrder->so_trl_amt_2 . '</soTrl2Amt>';
				$qdocBody .= '<soTrl3Amt>' . $salesOrder->so_trl_amt_3 . '</soTrl3Amt>';
				$qdocFooter = '</salesOrder>
							</dsSalesOrder>
						</maintainSalesOrder>
					</soapenv:Body>
				</soapenv:Envelope>';
				break;

			case 'update':
				// XML Qxtend
				$qdocHead = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>
						<maintainSalesOrder>
						<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>ERP3_2</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>';


				$qdocBody = '<dsSalesOrder>
								<salesOrder>
									<operation>M</operation>
									<soNbr>' . $salesOrder->so_nbr . '</soNbr>
									<soCust>' . $salesOrder->sold_to . '</soCust>
									<soShip>' . $salesOrder->ship_to . '</soShip>
									<soOrdDate>' . $salesOrder->order_date . '</soOrdDate>
									<soDueDate>' . $salesOrder->need_date . '</soDueDate>
									<soCrTerms>' . $salesOrder->so_ct_code . '</soCrTerms>
									<soTaxc1>' . $salesOrder->so_taxc . '</soTaxc1>';
				if ($salesOrder->so_is_taxable == 'Yes') {
					$qdocBody .= '<soTaxable1>true</soTaxable1>';
				} else {
					$qdocBody .= '<soTaxable1>false</soTaxable1>';
				}

				if ($salesOrder->so_is_taxin == 'Yes') {
					$qdocBody .= '<taxIn>true</taxIn>';
				} else {
					$qdocBody .= '<taxIn>false</taxIn>';
				}

				$qdocBody .= '<soSlspsn>' . $salesOrder->so_sales_person . '</soSlspsn>
										<soRmks>' . $salesOrder->remark . '</soRmks>
										<soPo>' . $salesOrder->so_nbr . '</soPo>';

				foreach ($salesOrder->getSODetail as $soDetail) {
					if ($soDetail->line_deleted == 'No') {
						$qdocBody .= '<salesOrderDetail>
									<line>' . $soDetail->line_detail . '</line>
									<sodPart>' . $soDetail->item_code . '</sodPart>
									<sodQtyOrd>' . $soDetail->qty_order . '</sodQtyOrd>
									<sodListPr>' . $soDetail->list_price . '</sodListPr>
									<discount>' . $soDetail->item_discount . '</discount>
									<sodLoc>' . $soDetail->sod_loc . '</sodLoc>';
						if ($soDetail->sod_is_taxable == 'Yes') {
							$qdocBody .= '<sodTaxable>true</sodTaxable>';
						} else {
							$qdocBody .= '<sodTaxable>false</sodTaxable>';
						}
						$qdocBody .= '<sodTaxc>' . $soDetail->sod_taxc . '</sodTaxc>
							<sodUm>' . $soDetail->item_um . '</sodUm>
							</salesOrderDetail>';
					} else {
						$qdocBody .= '<salesOrderDetail>
									<operation>R</operation>
									<line>' . $soDetail->line_detail . '</line>
							</salesOrderDetail>';
					}
				}
				$qdocFooter = '</salesOrder>
							</dsSalesOrder>
						</maintainSalesOrder>
					</soapenv:Body>
				</soapenv:Envelope>';
				break;

			case 'delete':
				// XML Qxtend
				$qdocHead = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>
						<maintainSalesOrder>
						<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>ERP3_2</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>';

				$qdocBody = '<dsSalesOrder>
								<salesOrder>
									<operation>R</operation>
									<soNbr>' . $soNumber . '</soNbr>';
				$qdocFooter = '</salesOrder>
							</dsSalesOrder>
						</maintainSalesOrder>
					</soapenv:Body>
				</soapenv:Envelope>';
				break;
		}

		$qdocRequest = $qdocHead . $qdocBody . $qdocFooter;

		$curlOptions = array(
			CURLOPT_URL => $qxUrl,
			CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
			CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
			CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
			CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false
		);

		$getInfo = '';
		$httpCode = 0;
		$curlErrno = 0;
		$curlError = '';


		$qdocResponse = '';

		$curl = curl_init();
		if ($curl) {
			curl_setopt_array($curl, $curlOptions);
			$qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
			//
			$curlErrno = curl_errno($curl);
			$curlError = curl_error($curl);
			$first = true;
			foreach (curl_getinfo($curl) as $key => $value) {
				if (gettype($value) != 'array') {
					if (!$first) $getInfo .= ", ";
					$getInfo = $getInfo . $key . '=>' . $value;
					$first = false;
					if ($key == 'http_code') $httpCode = $value;
				}
			}
			curl_close($curl);
		}

		if (is_bool($qdocResponse)) {

			DB::rollBack();
			return false;
		}
		$xmlResp = simplexml_load_string($qdocResponse);
		// dd($xmlResp);
		$xmlResp->registerXPathNamespace('ns1', 'urn:schemas-qad-com:xml-services');
		$qdocResult = (string) $xmlResp->xpath('//ns1:result')[0];

		$errorMessage = '';

		if ($qdocResult != 'success' || $qdocResult != 'warning') {
			$xmlResp->registerXPathNamespace('ns3', 'urn:schemas-qad-com:xml-services:common');
			$errMsgs = $xmlResp->xpath('//ns3:tt_msg_desc');
			$errorMessage = '';
			foreach ($errMsgs as $err) {
				$errorMessage .= $err;
			}
		}

		return [$qdocResult, $errorMessage];
	}

	public function qxSalesOrderMonthly($domain_id, $soNumber, $action)
	{
		$qxwsa = qxwsa::where('domain_id', $domain_id)->first();
		$domain = Domain::where('id', $domain_id)->first();
		$salesOrder = SOMstr::with(['getSODetail'])->where('so_nbr', $soNumber)->first();
		$qxUrl = $qxwsa->qx_url;
		$timeout = 0;
		$receiver = 'risis';

		switch ($action) {
			case 'create':
				// XML Qxtend
				$qdocHead = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>
						<maintainSalesOrder>
						<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>ERP3_2</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>';

				$qdocBody = '<dsSalesOrder>
								<salesOrder>
									<soNbr>' . $salesOrder->so_nbr . '</soNbr>
									<soCust>' . $salesOrder->sold_to . '</soCust>
									<soShip>' . $salesOrder->ship_to . '</soShip>
									<soOrdDate>' . $salesOrder->order_date . '</soOrdDate>
									<soDueDate>' . $salesOrder->need_date . '</soDueDate>
									<soCrTerms>' . $salesOrder->so_ct_code . '</soCrTerms>';


				if ($salesOrder->so_fr_list != '') {
					$qdocBody .= '<soFrList>' . $salesOrder->so_fr_list . '</soFrList>
								<soFrTerms>' . $salesOrder->so_fr_terms . '</soFrTerms>
								<calcFr>true</calcFr>';
				}

				$qdocBody .= '<soTaxc1>' . $salesOrder->so_taxc . '</soTaxc1>';
				if ($salesOrder->so_is_taxable == 'Yes') {
					$qdocBody .= '<soTaxable1>true</soTaxable1>';
				} else {
					$qdocBody .= '<soTaxable1>false</soTaxable1>';
				}

				// if ($salesOrder->so_is_taxin == 'Yes') {
				// 	$qdocBody .= '<taxIn>true</taxIn>';
				// } else {
				// 	$qdocBody .= '<taxIn>false</taxIn>';
				// }

				$qdocBody .= '<soSlspsn>' . $salesOrder->so_sales_person . '</soSlspsn>
										<soRmks>' . $salesOrder->remark . '</soRmks>
										<soPo>' . $salesOrder->so_po . '</soPo>';

				foreach ($salesOrder->getSODetail as $soDetail) {
					$qdocBody .= '<salesOrderDetail>
									<line>' . $soDetail->line_detail . '</line>
									<sodPart>' . $soDetail->item_code . '</sodPart>
									<sodQtyOrd>' . $soDetail->qty_order . '</sodQtyOrd>
									<sodListPr>' . $soDetail->list_price . '</sodListPr>
									<discount>' . $soDetail->item_discount . '</discount>
									<sodLoc>' . $soDetail->sod_loc . '</sodLoc>';
					if ($soDetail->sod_is_taxable == 'Yes') {
						$qdocBody .= '<sodTaxable>true</sodTaxable>';
					} else {
						$qdocBody .= '<sodTaxable>false</sodTaxable>';
					}
					$qdocBody .= '<sodTaxc>' . $soDetail->sod_taxc . '</sodTaxc>
										<sodUm>' . $soDetail->item_um . '</sodUm>
										</salesOrderDetail>';
				}
				$qdocBody .= '<soTrl1Amt>' . $salesOrder->so_trl_amt_1 . '</soTrl1Amt>';
				$qdocBody .= '<soTrl2Amt>' . $salesOrder->so_trl_amt_2 . '</soTrl2Amt>';
				$qdocBody .= '<soTrl3Amt>' . $salesOrder->so_trl_amt_3 . '</soTrl3Amt>';
				$qdocFooter = '</salesOrder>
							</dsSalesOrder>
						</maintainSalesOrder>
					</soapenv:Body>
				</soapenv:Envelope>';
				break;

			case 'update':
				// XML Qxtend
				$qdocHead = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>
						<maintainSalesOrder>
						<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>ERP3_2</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>';


				$qdocBody = '<dsSalesOrder>
								<salesOrder>
									<operation>M</operation>
									<soNbr>' . $salesOrder->so_nbr . '</soNbr>
									<soCust>' . $salesOrder->sold_to . '</soCust>
									<soShip>' . $salesOrder->ship_to . '</soShip>
									<soOrdDate>' . $salesOrder->order_date . '</soOrdDate>
									<soDueDate>' . $salesOrder->need_date . '</soDueDate>
									<soCrTerms>' . $salesOrder->so_ct_code . '</soCrTerms>
									<soTaxc1>' . $salesOrder->so_taxc . '</soTaxc1>';
				if ($salesOrder->so_is_taxable == 'Yes') {
					$qdocBody .= '<soTaxable1>true</soTaxable1>';
				} else {
					$qdocBody .= '<soTaxable1>false</soTaxable1>';
				}

				if ($salesOrder->so_is_taxin == 'Yes') {
					$qdocBody .= '<taxIn>true</taxIn>';
				} else {
					$qdocBody .= '<taxIn>false</taxIn>';
				}

				$qdocBody .= '<soSlspsn>' . $salesOrder->so_sales_person . '</soSlspsn>
										<soRmks>' . $salesOrder->remark . '</soRmks>
										<soPo>' . $salesOrder->so_po . '</soPo>';

				foreach ($salesOrder->getSODetail as $soDetail) {
					$qdocBody .= '<salesOrderDetail>
									<line>' . $soDetail->line_detail . '</line>
									<sodPart>' . $soDetail->item_code . '</sodPart>
									<sodQtyOrd>' . $soDetail->qty_order . '</sodQtyOrd>
									<sodListPr>' . $soDetail->list_price . '</sodListPr>
									<discount>' . $soDetail->item_discount . '</discount>
									<sodLoc>' . $soDetail->sod_loc . '</sodLoc>';
					if ($soDetail->sod_is_taxable == 'Yes') {
						$qdocBody .= '<sodTaxable>true</sodTaxable>';
					} else {
						$qdocBody .= '<sodTaxable>false</sodTaxable>';
					}
					$qdocBody .= '<sodTaxc>' . $soDetail->sod_taxc . '</sodTaxc>
										<sodUm>' . $soDetail->item_um . '</sodUm>
										</salesOrderDetail>';
				}
				$qdocFooter = '</salesOrder>
							</dsSalesOrder>
						</maintainSalesOrder>
					</soapenv:Body>
				</soapenv:Envelope>';
				break;

			case 'delete':
				// XML Qxtend
				$qdocHead = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>
						<maintainSalesOrder>
						<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>ERP3_2</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>';

				$qdocBody = '<dsSalesOrder>
								<salesOrder>
									<operation>R</operation>
									<soNbr>' . $soNumber . '</soNbr>';
				$qdocFooter = '</salesOrder>
							</dsSalesOrder>
						</maintainSalesOrder>
					</soapenv:Body>
				</soapenv:Envelope>';
				break;
		}

		$qdocRequest = $qdocHead . $qdocBody . $qdocFooter;

		$curlOptions = array(
			CURLOPT_URL => $qxUrl,
			CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
			CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
			CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
			CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false
		);

		$getInfo = '';
		$httpCode = 0;
		$curlErrno = 0;
		$curlError = '';


		$qdocResponse = '';

		$curl = curl_init();
		if ($curl) {
			curl_setopt_array($curl, $curlOptions);
			$qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
			//
			$curlErrno = curl_errno($curl);
			$curlError = curl_error($curl);
			$first = true;
			foreach (curl_getinfo($curl) as $key => $value) {
				if (gettype($value) != 'array') {
					if (!$first) $getInfo .= ", ";
					$getInfo = $getInfo . $key . '=>' . $value;
					$first = false;
					if ($key == 'http_code') $httpCode = $value;
				}
			}
			curl_close($curl);
		}

		// dd($qdocResponse);

		if (is_bool($qdocResponse)) {

			DB::rollBack();
			return false;
		}
		$xmlResp = simplexml_load_string($qdocResponse);
		// dd($xmlResp);
		$xmlResp->registerXPathNamespace('ns1', 'urn:schemas-qad-com:xml-services');
		$qdocResult = (string) $xmlResp->xpath('//ns1:result')[0];

		$errorMessage = '';

		if ($qdocResult != 'success' || $qdocResult != 'warning') {
			$xmlResp->registerXPathNamespace('ns3', 'urn:schemas-qad-com:xml-services:common');
			$errMsgs = $xmlResp->xpath('//ns3:tt_msg_desc');
			$errorMessage = '';
			foreach ($errMsgs as $err) {
				$errorMessage .= $err;
			}
		}

		return [$qdocResult, $errorMessage];
	}

	public function qxPurchaseOrder($domain_id, $poNumber, $newLine, $qxtendAction)
	{
		$qxwsa = qxwsa::where('domain_id', $domain_id)->first();
		$domain = Domain::where('id', $domain_id)->first();
		// $poMaster = POMstr::with(['getPODet'])->where('po_domain', $domain_id)
		// 	->where('po_nbr', $poNumber)->first();

		$poMaster = POMstr::query();

		if (count($newLine) > 0) {
			$poMaster = $poMaster->with(['getPODet' => function ($q) use ($newLine) {
				$q->whereIn('pod_line', $newLine);
			}]);
		} else {
			$poMaster = $poMaster->with(['getPODet']);
		}

		$poMaster = $poMaster->where('po_domain', $domain_id)->where('po_nbr', $poNumber)->first();

		// dd($poMaster);

		// dd($poNumber, $poMaster);

		$qxUrl = $qxwsa->qx_url;
		$timeout = 0;
		$receiver = 'risis';

		switch ($qxtendAction) {
			case 'create':
				// XML Qxtend
				$qdocHead = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>
						<maintainPurchaseOrder>
						<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>eB2_3</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>';

				$qdocBody = '<dsPurchaseOrder>
								<purchaseOrder>
									<poNbr>' . $poMaster->po_nbr . '</poNbr>
									<poVend>' . $poMaster->po_supplier . '</poVend>
									<poOrdDate>' . $poMaster->po_ord_date . '</poOrdDate>
									<poDueDate>' . $poMaster->po_due_date . '</poDueDate>
									<poRmks>' . $poMaster->po_remark . '</poRmks>
									<poSite>' . $poMaster->po_site . '</poSite>
									<poTaxable>' . $poMaster->getPODet[0]->pod_taxable . '</poTaxable>
									<poTaxc>' . $poMaster->getPODet[0]->pod_tax_class . '</poTaxc>';
				// <poCrTerms>' . $poMaster->getPODet[0]->pod_cr_terms . '</poCrTerms>';
				if ($poMaster->po_contract != null) {
					$qdocBody .= '<poContract>' . $poMaster->po_contract . '</poContract>';
				}




				foreach ($poMaster->getPODet as $poDetail) {
					$needSub = 'true';
					$needCC = 'true';
					$needProject = 'false';
					$qdocBody .= '<lineDetail>
							<line>' . $poDetail->pod_line . '</line>
							<podPart>' . $poDetail->pod_item_code . '</podPart>
                            <podLoc>' . $poDetail->pod_loc . '</podLoc>';

					if ($poDetail->pod_is_memo == 'Yes') {
						$qdocBody .= '<desc1>' . $poDetail->pod_item_desc . '</desc1>';
					}
					$qdocBody .= '<podQtyOrd>' . $poDetail->pod_qty_ord . '</podQtyOrd>
							<podUm>' . $poDetail->pod_um . '</podUm>
							<podDueDate>' . $poDetail->pod_due_date . '</podDueDate>
							<podNeed>' . $poDetail->pod_need_date . '</podNeed>
                            <podPurCost>' . $poDetail->pod_pur_cost . '</podPurCost>
							<podTaxable>' . $poDetail->pod_taxable . '</podTaxable>
							<podTaxc>' . $poDetail->pod_tax_class . '</podTaxc>';

					if ($poDetail->pod_is_memo == 'Yes' and $poDetail->pod_account != null) {
						$qdocBody .= '<podAcct>' . $poDetail->pod_account . '</podAcct>';

						// WSA to check sub account, cost center, project
						$wsaAccountChilds = (new WSAServices())->wsaAccountChild($poMaster->po_domain, $poDetail->pod_account);
						if ($wsaAccountChilds[0] == 'true') {
							$needSub = $wsaAccountChilds[1][0]->t_need_sub;
							$needCC = $wsaAccountChilds[1][0]->t_need_cc;
							$needProject = $wsaAccountChilds[1][0]->t_need_project;
						}
					}

					if ($needSub == 'true') {
						$qdocBody .= '<podSub>' . $poDetail->pod_sub_acc . '</podSub>';
					}

					if ($needCC == 'true') {
						$qdocBody .= '<podCc>' . $poDetail->pod_cost_center . '</podCc>';
					}

					if ($needProject == 'true') {
						$qdocBody .= '<podProject>' . $poDetail->pod_project . '</podProject>';
					}
					if ($poDetail->pod_comment) {
						$qdocBody .= '<podcmmts>true</podcmmts>
						<lineDetailTransComment>
						<cmtSeq>1</cmtSeq>
						<cdSeq>1</cdSeq>
						<cmtCmmt>' . $poDetail->pod_comment . '</cmtCmmt>
						</lineDetailTransComment>';
					}

					$qdocBody .= '</lineDetail>';
				}
				$qdocFooter = '</purchaseOrder>
							</dsPurchaseOrder>
						</maintainPurchaseOrder>
					</soapenv:Body>
				</soapenv:Envelope>';

				$qdocRequest = $qdocHead . $qdocBody . $qdocFooter;
				break;

			case 'update':
				// XML Qxtend
				$qdocHead = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>
						<maintainPurchaseOrder>
						<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>eB2_3</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>';

				$qdocBody = '<dsPurchaseOrder>
								<purchaseOrder>
									<operation>M</operation>
									<poNbr>' . $poMaster->po_nbr . '</poNbr>
									<poVend>' . $poMaster->po_supplier . '</poVend>
									<poShip>' . $poMaster->po_shipto . '</poShip>
									<poOrdDate>' . $poMaster->po_ord_date . '</poOrdDate>
									<poDueDate>' . $poMaster->po_due_date . '</poDueDate>
									<poRmks>' . $poMaster->po_remark . '</poRmks>
									<poSite>' . $poMaster->po_site . '</poSite>
									<poRev>' . $poMaster->po_rev . '</poRev>';

				foreach ($poMaster->getPODet as $poDetail) {
					if ($poDetail->pod_deleted == 'No') {
						$qdocBody .= '<lineDetail>
							<line>' . $poDetail->pod_line . '</line>
							<podPart>' . $poDetail->pod_item_code . '</podPart>
							<podQtyOrd>' . $poDetail->pod_qty_ord . '</podQtyOrd>
							<podUm>' . $poDetail->pod_um . '</podUm>
							<podDueDate>' . $poDetail->pod_due_date . '</podDueDate>
							<podNeed>' . $poDetail->pod_need_date . '</podNeed>
							<podPurCost>' . $poDetail->pod_pur_cost . '</podPurCost>
							<podSub>' . $poDetail->pod_sub_acc . '</podSub>
							<podCc>' . $poDetail->pod_cost_center . '</podCc>
							<podProject>' . $poDetail->pod_project . '</podProject>';
						if ($poDetail->pod_comment) {
							$qdocBody .= '<podcmmts>true</podcmmts>
								<lineDetailTransComment>
								<cmtSeq>1</cmtSeq>
								<cdSeq>1</cdSeq>
								<cmtCmmt>' . $poDetail->pod_comment . '</cmtCmmt>
								</lineDetailTransComment>';
						}
						$qdocBody .= '</lineDetail>';
					} else {
						$qdocBody .= '<lineDetail>
							<operation>R</operation>
							<line>' . $poDetail->pod_line . '</line>
							</lineDetail>';
					}
				}
				$qdocFooter = '</purchaseOrder>
							</dsPurchaseOrder>
						</maintainPurchaseOrder>
					</soapenv:Body>
				</soapenv:Envelope>';

				$qdocRequest = $qdocHead . $qdocBody . $qdocFooter;
				break;

			case 'remove':
				// XML Qxtend
				$qdocHead = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>
						<maintainPurchaseOrder>
						<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>eB2_3</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>';

				$qdocBody = '<dsPurchaseOrder>
								<purchaseOrder>
									<operation>R</operation>
									<poNbr>' . $poMaster->po_nbr . '</poNbr>';
				$qdocFooter = '</purchaseOrder>
							</dsPurchaseOrder>
						</maintainPurchaseOrder>
					</soapenv:Body>
				</soapenv:Envelope>';

				$qdocRequest = $qdocHead . $qdocBody . $qdocFooter;
				break;
		}

		$qdocRequest = str_replace('&', '&amp;', $qdocRequest);

		// dd($qdocRequest);

		$curlOptions = array(
			CURLOPT_URL => $qxUrl,
			CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
			CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
			CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
			CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false
		);

		$getInfo = '';
		$httpCode = 0;
		$curlErrno = 0;
		$curlError = '';


		$qdocResponse = '';

		$curl = curl_init();
		if ($curl) {
			curl_setopt_array($curl, $curlOptions);
			$qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
			//
			$curlErrno = curl_errno($curl);
			$curlError = curl_error($curl);
			$first = true;
			foreach (curl_getinfo($curl) as $key => $value) {
				if (gettype($value) != 'array') {
					if (!$first) $getInfo .= ", ";
					$getInfo = $getInfo . $key . '=>' . $value;
					$first = false;
					if ($key == 'http_code') $httpCode = $value;
				}
			}
			curl_close($curl);
		}


		// dd($qdocRequest,$qdocResponse);
		if (is_bool($qdocResponse)) {

			DB::rollBack();
			return false;
		}
		$xmlResp = simplexml_load_string($qdocResponse);

		$xmlResp->registerXPathNamespace('ns1', 'urn:schemas-qad-com:xml-services');
		$qdocResult = (string) $xmlResp->xpath('//ns1:result')[0];

		$errorMessage = '';

		if ($qdocResult != 'success' || $qdocResult != 'warning') {
			$xmlResp->registerXPathNamespace('ns3', 'urn:schemas-qad-com:xml-services:common');
			$errMsgs = $xmlResp->xpath('//ns3:tt_msg_desc');
			$errorMessage = '';
			foreach ($errMsgs as $err) {
				$errorMessage .= $err;
			}
		}

		return [$qdocResult, $errorMessage];
	}

	public function qxPurchaseOrderMonthly($domain_id, $poNumber, $newLine, $qxtendAction)
	{
		$qxwsa = qxwsa::where('domain_id', $domain_id)->first();
		$domain = Domain::where('id', $domain_id)->first();
		// $poMaster = POMstr::with(['getPODet'])->where('po_domain', $domain_id)
		// 	->where('po_nbr', $poNumber)->first();

		$poMaster = POMstr::query()->withoutGlobalScopes();

		if (count($newLine) > 0) {
			$poMaster = $poMaster->with(['getPODet' => function ($q) use ($newLine) {
				$q->whereIn('pod_line', $newLine);
			}]);
		} else {
			$poMaster = $poMaster->with(['getPODet']);
		}

		$poMaster = $poMaster->where('po_domain', $domain_id)->where('po_nbr', $poNumber)->first();

		// dd($poNumber, $poMaster);

		$qxUrl = $qxwsa->qx_url;
		$timeout = 0;
		$receiver = 'risis';

		switch ($qxtendAction) {
			case 'create':
				// XML Qxtend
				$qdocHead = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>
						<maintainPurchaseOrder>
						<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>eB2_3</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>';

				$qdocBody = '<dsPurchaseOrder>
								<purchaseOrder>
									<poNbr>' . $poMaster->po_nbr . '</poNbr>
									<poVend>' . $poMaster->po_supplier . '</poVend>
									<poShip>' . $poMaster->po_shipto . '</poShip>
									<poOrdDate>' . $poMaster->po_ord_date . '</poOrdDate>
									<poDueDate>' . $poMaster->po_due_date . '</poDueDate>
									<poRmks>' . $poMaster->po_remark . '</poRmks>
									<poSite>' . $poMaster->po_site . '</poSite>
									<poTaxable>' . $poMaster->getPODet[0]->pod_taxable . '</poTaxable>
									<poTaxc>' . $poMaster->getPODet[0]->pod_tax_class . '</poTaxc>';
				// <poCrTerms>' . $poMaster->getPODet[0]->pod_cr_terms . '</poCrTerms>';
				if ($poMaster->po_contract != null) {
					$qdocBody .= '<poContract>' . $poMaster->po_contract . '</poContract>';
				}




				foreach ($poMaster->getPODet as $poDetail) {
					$needSub = 'true';
					$needCC = 'true';
					$needProject = 'false';
					$qdocBody .= '<lineDetail>
							<line>' . $poDetail->pod_line . '</line>
							<podPart>' . $poDetail->pod_item_code . '</podPart>';

					if ($poDetail->pod_is_memo == 'Yes') {
						$qdocBody .= '<desc1>' . $poDetail->pod_item_desc . '</desc1>';
					}
					$qdocBody .= '<podQtyOrd>' . $poDetail->pod_qty_ord . '</podQtyOrd>
							<podUm>' . $poDetail->pod_um . '</podUm>
							<podDueDate>' . $poDetail->pod_due_date . '</podDueDate>
							<podNeed>' . $poDetail->pod_need_date . '</podNeed>
                            <podPurCost>' . $poDetail->pod_pur_cost . '</podPurCost>
							<podTaxable>' . $poDetail->pod_taxable . '</podTaxable>
							<podTaxc>' . $poDetail->pod_tax_class . '</podTaxc>';

					if ($poDetail->pod_is_memo == 'Yes' and $poDetail->pod_account != null) {
						$qdocBody .= '<podAcct>' . $poDetail->pod_account . '</podAcct>';

						// WSA to check sub account, cost center, project
						$wsaAccountChilds = (new WSAServices())->wsaAccountChild($poMaster->po_domain, $poDetail->pod_account);
						if ($wsaAccountChilds[0] == 'true') {
							$needSub = $wsaAccountChilds[1][0]->t_need_sub;
							$needCC = $wsaAccountChilds[1][0]->t_need_cc;
							$needProject = $wsaAccountChilds[1][0]->t_need_project;
						}
					}

					if ($needSub == 'true') {
						$qdocBody .= '<podSub>' . $poDetail->pod_sub_acc . '</podSub>';
					}

					if ($needCC == 'true') {
						$qdocBody .= '<podCc>' . $poDetail->pod_cost_center . '</podCc>';
					}

					if ($needProject == 'true') {
						$qdocBody .= '<podProject>' . $poDetail->pod_project . '</podProject>';
					}
					if ($poDetail->pod_comment) {
						$qdocBody .= '<podcmmts>true</podcmmts>
						<lineDetailTransComment>
						<cmtSeq>1</cmtSeq>
						<cdSeq>1</cdSeq>
						<cmtCmmt>' . $poDetail->pod_comment . '</cmtCmmt>
						</lineDetailTransComment>';
					}

					$qdocBody .= '</lineDetail>';
				}
				$qdocFooter = '</purchaseOrder>
							</dsPurchaseOrder>
						</maintainPurchaseOrder>
					</soapenv:Body>
				</soapenv:Envelope>';

				$qdocRequest = $qdocHead . $qdocBody . $qdocFooter;
				break;

			case 'update':
				// XML Qxtend
				$qdocHead = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>
						<maintainPurchaseOrder>
						<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>eB2_3</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>';

				$qdocBody = '<dsPurchaseOrder>
								<purchaseOrder>
									<operation>M</operation>
									<poNbr>' . $poMaster->po_nbr . '</poNbr>
									<poVend>' . $poMaster->po_supplier . '</poVend>
									<poShip>' . $poMaster->po_shipto . '</poShip>
									<poOrdDate>' . $poMaster->po_ord_date . '</poOrdDate>
									<poDueDate>' . $poMaster->po_due_date . '</poDueDate>
									<poRmks>' . $poMaster->po_remark . '</poRmks>
									<poSite>' . $poMaster->po_site . '</poSite>
									<poRev>' . $poMaster->po_rev . '</poRev>';

				foreach ($poMaster->getPODet as $poDetail) {
					if ($poDetail->pod_deleted == 'No') {
						$qdocBody .= '<lineDetail>
							<line>' . $poDetail->pod_line . '</line>
							<podPart>' . $poDetail->pod_item_code . '</podPart>
							<podQtyOrd>' . $poDetail->pod_qty_ord . '</podQtyOrd>
							<podUm>' . $poDetail->pod_um . '</podUm>
							<podDueDate>' . $poDetail->pod_due_date . '</podDueDate>
							<podNeed>' . $poDetail->pod_need_date . '</podNeed>
							<podPurCost>' . $poDetail->pod_pur_cost . '</podPurCost>
							<podSub>' . $poDetail->pod_sub_acc . '</podSub>
							<podCc>' . $poDetail->pod_cost_center . '</podCc>
							<podProject>' . $poDetail->pod_project . '</podProject>';
						if ($poDetail->pod_comment) {
							$qdocBody .= '<podcmmts>true</podcmmts>
								<lineDetailTransComment>
								<cmtSeq>1</cmtSeq>
								<cdSeq>1</cdSeq>
								<cmtCmmt>' . $poDetail->pod_comment . '</cmtCmmt>
								</lineDetailTransComment>';
						}
						$qdocBody .= '</lineDetail>';
					} else {
						$qdocBody .= '<lineDetail>
							<operation>R</operation>
							<line>' . $poDetail->pod_line . '</line>
							</lineDetail>';
					}
				}
				$qdocFooter = '</purchaseOrder>
							</dsPurchaseOrder>
						</maintainPurchaseOrder>
					</soapenv:Body>
				</soapenv:Envelope>';

				$qdocRequest = $qdocHead . $qdocBody . $qdocFooter;
				break;

			case 'remove':
				// XML Qxtend
				$qdocHead = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>
						<maintainPurchaseOrder>
						<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>eB2_3</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>';

				$qdocBody = '<dsPurchaseOrder>
								<purchaseOrder>
									<operation>R</operation>
									<poNbr>' . $poMaster->po_nbr . '</poNbr>';
				$qdocFooter = '</purchaseOrder>
							</dsPurchaseOrder>
						</maintainPurchaseOrder>
					</soapenv:Body>
				</soapenv:Envelope>';

				$qdocRequest = $qdocHead . $qdocBody . $qdocFooter;
				break;
		}

		$qdocRequest = str_replace('&', '&amp;', $qdocRequest);

		$curlOptions = array(
			CURLOPT_URL => $qxUrl,
			CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
			CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
			CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
			CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false
		);

		$getInfo = '';
		$httpCode = 0;
		$curlErrno = 0;
		$curlError = '';


		$qdocResponse = '';

		$curl = curl_init();
		if ($curl) {
			curl_setopt_array($curl, $curlOptions);
			$qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
			//
			$curlErrno = curl_errno($curl);
			$curlError = curl_error($curl);
			$first = true;
			foreach (curl_getinfo($curl) as $key => $value) {
				if (gettype($value) != 'array') {
					if (!$first) $getInfo .= ", ";
					$getInfo = $getInfo . $key . '=>' . $value;
					$first = false;
					if ($key == 'http_code') $httpCode = $value;
				}
			}
			curl_close($curl);
		}


		// dd($qdocRequest,$qdocResponse);
		if (is_bool($qdocResponse)) {

			DB::rollBack();
			return false;
		}
		$xmlResp = simplexml_load_string($qdocResponse);

		$xmlResp->registerXPathNamespace('ns1', 'urn:schemas-qad-com:xml-services');
		$qdocResult = (string) $xmlResp->xpath('//ns1:result')[0];

		$errorMessage = '';

		if ($qdocResult != 'success' || $qdocResult != 'warning') {
			$xmlResp->registerXPathNamespace('ns3', 'urn:schemas-qad-com:xml-services:common');
			$errMsgs = $xmlResp->xpath('//ns3:tt_msg_desc');
			$errorMessage = '';
			foreach ($errMsgs as $err) {
				$errorMessage .= $err;
			}
		}

		return [$qdocResult, $errorMessage];
	}

	public function qxTransferSingleItem($itemTFMstr, $detail, $qtyPick)
	{
		$qxwsa = qxwsa::where('domain_id', $itemTFMstr->itm_domain_id)->first();
		$domain = Domain::where('id', $itemTFMstr->itm_domain_id)->first();
		$qxUrl = $qxwsa->qx_url;
		$timeout = 0;
		$receiver = 'risis';

		// XML Qxtend
		$qdocHead = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>';

		if ($detail->itd_lot_stock_in != '') {
			$qdocHead .= '<transferInvCreateShipper>';
			$version = 'ERP3_3';
		} else {
			$qdocHead .= '<transferInvSingleItem>';
			$version = 'ERP3_1';
		}

		$qdocHead .= '<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>' . $version . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>';

		$qdocBody = '<dsItem>
						<item>
							<part>' . $detail->itd_item_code . '</part>
							<itemDetail>
								<lotserialQty>' . $qtyPick . '</lotserialQty>
								<effDate>' . $itemTFMstr->itm_confirm_date . '</effDate>
								<nbr>' . $itemTFMstr->itm_nbr . '</nbr>
								<rmks>' . $itemTFMstr->itm_remark . '</rmks>
								<siteFrom>' . $detail->itd_site_stock_out . '</siteFrom>
								<locFrom>' . $detail->itd_loc_stock_out . '</locFrom>
								<lotserFrom>' . $detail->itd_lot_stock_out . '</lotserFrom>
								<siteTo>' . $itemTFMstr->itm_site_stock_in . '</siteTo>
								<locTo>' . $itemTFMstr->itm_loc_stock_in . '</locTo>';
		if ($detail->itd_lot_stock_in != '') {
			$qdocBody .= '<lotserTo>' . $detail->itd_lot_stock_in . '</lotserTo>';
		}
		$qdocBody .= '
								<yn>true</yn>
								<yn2>true</yn2>
							</itemDetail>';

		$qdocFooter = '</item>
					</dsItem>';

		if ($detail->itd_lot_stock_in != '') {
			$qdocFooter .= '</transferInvCreateShipper>';
		} else {
			$qdocFooter .= '</transferInvSingleItem>';
		}

		$qdocFooter .= '</soapenv:Body>
		</soapenv:Envelope>';

		$qdocRequest = $qdocHead . $qdocBody . $qdocFooter;

		$curlOptions = array(
			CURLOPT_URL => $qxUrl,
			CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
			CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
			CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
			CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false
		);

		$getInfo = '';
		$httpCode = 0;
		$curlErrno = 0;
		$curlError = '';


		$qdocResponse = '';

		$curl = curl_init();
		if ($curl) {
			curl_setopt_array($curl, $curlOptions);
			$qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
			//
			$curlErrno = curl_errno($curl);
			$curlError = curl_error($curl);
			$first = true;
			foreach (curl_getinfo($curl) as $key => $value) {
				if (gettype($value) != 'array') {
					if (!$first) $getInfo .= ", ";
					$getInfo = $getInfo . $key . '=>' . $value;
					$first = false;
					if ($key == 'http_code') $httpCode = $value;
				}
			}
			curl_close($curl);
		}

		// dd($qdocResponse);

		if (is_bool($qdocResponse)) {

			DB::rollBack();
			return false;
		}
		$xmlResp = simplexml_load_string($qdocResponse);
		// dd($xmlResp);
		$xmlResp->registerXPathNamespace('ns1', 'urn:schemas-qad-com:xml-services');
		$qdocResult = (string) $xmlResp->xpath('//ns1:result')[0];

		$errorMessage = '';

		if ($qdocResult == 'error') {
			$xmlResp->registerXPathNamespace('ns3', 'urn:schemas-qad-com:xml-services:common');
			$errMsgs = $xmlResp->xpath('//ns3:tt_msg_desc');
			$errorMessage = '';
			foreach ($errMsgs as $err) {
				$errorMessage .= $err;
			}
		}

		return [$qdocResult, $errorMessage];
	}

	//tf single item complaint
	public function qxTransferSingleItemComplaint($ComplainMstr, $detail, $qtyReturn)
	{
		$qxwsa = qxwsa::where('domain_id', $ComplainMstr->crm_domain_id)->first();
		$domain = Domain::where('id', $ComplainMstr->crm_domain_id)->first();
		$qxUrl = $qxwsa->qx_url;

		$timeout = 0;
		$receiver = 'risis';

		// XML Qxtend
		$qdocHead = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>';

		if ($detail->crd_lot_stock_in != '') {
			$qdocHead .= '<transferInvCreateShipper>';
			$version = 'ERP3_3';
		} else {
			$qdocHead .= '<transferInvSingleItem>';
			$version = 'ERP3_1';
		}

		$qdocHead .= '<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>' . $version . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>';

		$qdocBody = '<dsItem>
						<item>
							<part>' . $detail->crd_item_code . '</part>
							<itemDetail>
								<lotserialQty>' . $qtyReturn . '</lotserialQty>
								<effDate>' . $ComplainMstr->crm_confirm_date . '</effDate>
								<nbr>' . $ComplainMstr->crm_nbr . '</nbr>
								<rmks>' . $ComplainMstr->crm_remark . '</rmks>
								<siteFrom>' . $detail->crd_site_stock_out . '</siteFrom>
								<locFrom>' . $detail->crd_loc_stock_out . '</locFrom>
								<lotserFrom>' . $detail->crd_lot_stock_out . '</lotserFrom>
								<siteTo>' . $ComplainMstr->crm_site_stock_in . '</siteTo>
								<locTo>' . $ComplainMstr->crm_loc_stock_in . '</locTo>';
		// if ($detail->crd_lot_stock_in != '') {
		// $qdocBody .= '<lotserTo>' . $detail->crd_lot_stock_in . '</lotserTo>';
		// }
		$qdocBody .= '
								<yn>true</yn>
								<yn2>true</yn2>
							</itemDetail>';

		$qdocFooter = '</item>
					</dsItem>';

		if ($detail->crd_lot_stock_in != '') {
			$qdocFooter .= '</transferInvCreateShipper>';
		} else {
			$qdocFooter .= '</transferInvSingleItem>';
		}

		$qdocFooter .= '</soapenv:Body>
		</soapenv:Envelope>';

		$qdocRequest = $qdocHead . $qdocBody . $qdocFooter;

		$curlOptions = array(
			CURLOPT_URL => $qxUrl,
			CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
			CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
			CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
			CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false
		);

		$getInfo = '';
		$httpCode = 0;
		$curlErrno = 0;
		$curlError = '';


		$qdocResponse = '';

		$curl = curl_init();
		if ($curl) {
			curl_setopt_array($curl, $curlOptions);
			$qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
			//
			$curlErrno = curl_errno($curl);
			$curlError = curl_error($curl);
			$first = true;
			foreach (curl_getinfo($curl) as $key => $value) {
				if (gettype($value) != 'array') {
					if (!$first) $getInfo .= ", ";
					$getInfo = $getInfo . $key . '=>' . $value;
					$first = false;
					if ($key == 'http_code') $httpCode = $value;
				}
			}
			curl_close($curl);
		}

		// dd($qdocResponse);

		if (is_bool($qdocResponse)) {

			DB::rollBack();
			return false;
		}
		$xmlResp = simplexml_load_string($qdocResponse);
		// dd($xmlResp);
		$xmlResp->registerXPathNamespace('ns1', 'urn:schemas-qad-com:xml-services');
		$qdocResult = (string) $xmlResp->xpath('//ns1:result')[0];

		$errorMessage = '';

		if ($qdocResult == 'error') {
			$xmlResp->registerXPathNamespace('ns3', 'urn:schemas-qad-com:xml-services:common');
			$errMsgs = $xmlResp->xpath('//ns3:tt_msg_desc');
			$errorMessage = '';
			foreach ($errMsgs as $err) {
				$errorMessage .= $err;
			}
		}

		return [$qdocResult, $errorMessage];
	}
	public function qxStockRequest($stockRequestMstr, $detail)
	{
		$qxwsa = qxwsa::where('domain_id', $stockRequestMstr->srm_domain_id)->first();
		$domain = Domain::where('id', $stockRequestMstr->srm_domain_id)->first();
		$qxUrl = $qxwsa->qx_url;
		$timeout = 0;
		$receiver = 'risis';

		// lotSerialQty from qty request -> qty pick
		switch ($stockRequestMstr->srm_type) {
			case 'In':
				$qdocRequest = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>
						<receiveInventory>
						<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>eB_2</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>
						<dsInventoryReceipt>
							<inventoryReceipt>
								<ptPart>' . $detail->srd_item_code . '</ptPart>
								<lotserialQty>' . $detail->srd_qty_pick . '</lotserialQty>
								<site>' . $detail->srd_site . '</site>
								<location>' . $detail->srd_loc . '</location>
								<lotserial>' . $detail->srd_lot . '</lotserial>
								<ordernbr>' . $stockRequestMstr->srm_nbr . '</ordernbr>
								<rmks>' . $stockRequestMstr->srm_remark . '</rmks>
								<effDate>' . $stockRequestMstr->srm_eff_date . '</effDate>
								<crAcct>' . $detail->srd_account . '</crAcct>
								<crSub>' . $detail->srd_sub_account . '</crSub>
								<crCc>' . $detail->srd_cost_center . '</crCc>
								<crProj>' . $detail->srd_project . '</crProj>
								<yn>true</yn>
								<yn1>true</yn1>
							</inventoryReceipt>
						</dsInventoryReceipt>
						</receiveInventory>
					</soapenv:Body>
					</soapenv:Envelope>';
				break;

			case 'Out':
				$qdocRequest = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>
						<issueInventory>
						<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>eB_2</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>
						<dsInventoryIssue>
							<inventoryIssue>
								<ptPart>' . $detail->srd_item_code . '</ptPart>
								<lotserialQty>' . $detail->srd_qty_pick . '</lotserialQty>
								<site>' . $detail->srd_site . '</site>
								<location>' . $detail->srd_loc . '</location>
								<lotserial>' . $detail->srd_lot . '</lotserial>
								<ordernbr>' . $stockRequestMstr->srm_nbr . '</ordernbr>
								<effDate>' . $stockRequestMstr->srm_eff_date . '</effDate>
								<drAcct>' . $detail->srd_account . '</drAcct>
								<drSub>' . $detail->srd_sub_account . '</drSub>
								<drCc>' . $detail->srd_cost_center . '</drCc>
								<drProj>' . $detail->srd_project . '</drProj>
								<yn>true</yn>
								<yn1>true</yn1>
							</inventoryIssue>
						</dsInventoryIssue>
						</issueInventory>
					</soapenv:Body>
					</soapenv:Envelope>';
				break;
		}

		$curlOptions = array(
			CURLOPT_URL => $qxUrl,
			CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
			CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
			CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
			CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false
		);

		$getInfo = '';
		$httpCode = 0;
		$curlErrno = 0;
		$curlError = '';


		$qdocResponse = '';

		$curl = curl_init();
		if ($curl) {
			curl_setopt_array($curl, $curlOptions);
			$qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
			//
			$curlErrno = curl_errno($curl);
			$curlError = curl_error($curl);
			$first = true;
			foreach (curl_getinfo($curl) as $key => $value) {
				if (gettype($value) != 'array') {
					if (!$first) $getInfo .= ", ";
					$getInfo = $getInfo . $key . '=>' . $value;
					$first = false;
					if ($key == 'http_code') $httpCode = $value;
				}
			}
			curl_close($curl);
		}

		// dd($qdocResponse);

		if (is_bool($qdocResponse)) {

			DB::rollBack();
			return false;
		}
		$xmlResp = simplexml_load_string($qdocResponse);
		// dd($xmlResp);
		$xmlResp->registerXPathNamespace('ns1', 'urn:schemas-qad-com:xml-services');
		$qdocResult = (string) $xmlResp->xpath('//ns1:result')[0];

		$errorMessage = '';

		if ($qdocResult != 'success' || $qdocResult != 'warning') {
			$xmlResp->registerXPathNamespace('ns3', 'urn:schemas-qad-com:xml-services:common');
			$errMsgs = $xmlResp->xpath('//ns3:tt_msg_desc');
			$errorMessage = '';
			foreach ($errMsgs as $err) {
				$errorMessage .= $err;
			}
		}

		return [$qdocResult, $errorMessage];
	}

	public function qxPackagingRequest($packagingRequestMstr, $detail)
	{
		$qxwsa = qxwsa::where('domain_id', $packagingRequestMstr->prm_domain_id)->first();
		$domain = Domain::where('id', $packagingRequestMstr->prm_domain_id)->first();
		$qxUrl = $qxwsa->qx_url;
		$timeout = 0;
		$receiver = 'risis';

		// lotSerialQty from qty request -> qty pick
		switch ($packagingRequestMstr->prm_type) {
			case 'In':
				$qdocRequest = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>
						<receiveInventory>
						<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>eB_2</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>
						<dsInventoryReceipt>
							<inventoryReceipt>
								<ptPart>' . $detail->prd_item_code . '</ptPart>
								<lotserialQty>' . $detail->prd_qty_pick . '</lotserialQty>
								<site>' . $detail->prd_site . '</site>
								<location>' . $detail->prd_loc . '</location>
								<lotserial>' . $detail->prd_lot . '</lotserial>
								<ordernbr>' . $packagingRequestMstr->prm_nbr . '</ordernbr>
								<rmks>' . $packagingRequestMstr->prm_remark . '</rmks>
								<effDate>' . $packagingRequestMstr->prm_eff_date . '</effDate>
								<crAcct>' . $detail->prd_account . '</crAcct>
								<crSub>' . $detail->prd_sub_account . '</crSub>
								<crCc>' . $detail->prd_cost_center . '</crCc>
								<crProj>' . $detail->prd_project . '</crProj>
								<yn>true</yn>
								<yn1>true</yn1>
							</inventoryReceipt>
						</dsInventoryReceipt>
						</receiveInventory>
					</soapenv:Body>
					</soapenv:Envelope>';
				break;

			case 'Out':
				$qdocRequest = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>
						<issueInventory>
						<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>eB_2</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>
						<dsInventoryIssue>
							<inventoryIssue>
								<ptPart>' . $detail->prd_item_code . '</ptPart>
								<lotserialQty>' . $detail->prd_qty_pick . '</lotserialQty>
								<site>' . $detail->prd_site . '</site>
								<location>' . $detail->prd_loc . '</location>
								<lotserial>' . $detail->prd_lot . '</lotserial>
								<ordernbr>' . $packagingRequestMstr->prm_nbr . '</ordernbr>
								<effDate>' . $packagingRequestMstr->prm_eff_date . '</effDate>
								<drAcct>' . $detail->prd_account . '</drAcct>
								<drSub>' . $detail->prd_sub_account . '</drSub>
								<drCc>' . $detail->prd_cost_center . '</drCc>
								<drProj>' . $detail->prd_project . '</drProj>
								<yn>true</yn>
								<yn1>true</yn1>
							</inventoryIssue>
						</dsInventoryIssue>
						</issueInventory>
					</soapenv:Body>
					</soapenv:Envelope>';
				break;
		}

		$curlOptions = array(
			CURLOPT_URL => $qxUrl,
			CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
			CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
			CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
			CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false
		);

		$getInfo = '';
		$httpCode = 0;
		$curlErrno = 0;
		$curlError = '';


		$qdocResponse = '';

		$curl = curl_init();
		if ($curl) {
			curl_setopt_array($curl, $curlOptions);
			$qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
			//
			$curlErrno = curl_errno($curl);
			$curlError = curl_error($curl);
			$first = true;
			foreach (curl_getinfo($curl) as $key => $value) {
				if (gettype($value) != 'array') {
					if (!$first) $getInfo .= ", ";
					$getInfo = $getInfo . $key . '=>' . $value;
					$first = false;
					if ($key == 'http_code') $httpCode = $value;
				}
			}
			curl_close($curl);
		}

		// dd($qdocResponse);

		if (is_bool($qdocResponse)) {

			DB::rollBack();
			return false;
		}
		$xmlResp = simplexml_load_string($qdocResponse);
		// dd($xmlResp);
		$xmlResp->registerXPathNamespace('ns1', 'urn:schemas-qad-com:xml-services');
		$qdocResult = (string) $xmlResp->xpath('//ns1:result')[0];

		$errorMessage = '';

		if ($qdocResult != 'success' || $qdocResult != 'warning') {
			$xmlResp->registerXPathNamespace('ns3', 'urn:schemas-qad-com:xml-services:common');
			$errMsgs = $xmlResp->xpath('//ns3:tt_msg_desc');
			$errorMessage = '';
			foreach ($errMsgs as $err) {
				$errorMessage .= $err;
			}
		}

		return [$qdocResult, $errorMessage];
	}

	public function qxLaborFeedback($domainMaster, $wo, $operationComplete)
	{
		$qxwsa = qxwsa::where('domain_id', $domainMaster->id)->first();
		$qxUrl = $qxwsa->qx_url;
		$timeout = 0;
		$receiver = 'risis';

		// XML Qxtend

		$qdocRequest = '<?xml version="1.0" encoding="UTF-8"?>
			<soapenv:Envelope
				xmlns="urn:schemas-qad-com:xml-services"
				xmlns:qcom="urn:schemas-qad-com:xml-services:common"
				xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
				xmlns:wsa="http://www.w3.org/2005/08/addressing">
				<soapenv:Header>
					<wsa:Action/>
					<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
					<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
					<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
					</wsa:ReferenceParameters>
					<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
					</wsa:ReplyTo>
				</soapenv:Header>
				<soapenv:Body>
					<recordLaborFeedbackByWO>
						<qcom:dsSessionContext>
							<qcom:ttContext>
								<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
								<qcom:propertyName>domain</qcom:propertyName>
								<qcom:propertyValue>' . $domainMaster->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
								<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
								<qcom:propertyName>scopeTransaction</qcom:propertyName>
								<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
								<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
								<qcom:propertyName>version</qcom:propertyName>
								<qcom:propertyValue>eB_4</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
								<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
								<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
								<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
								<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
								<qcom:propertyName>action</qcom:propertyName>
								<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
								<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
								<qcom:propertyName>entity</qcom:propertyName>
								<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
								<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
								<qcom:propertyName>email</qcom:propertyName>
								<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
								<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
								<qcom:propertyName>emailLevel</qcom:propertyName>
								<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>
						<dsWOLaborFeedback>
							<WOLaborFeedback>
								<wrNbr>' . $wo['wo_nbr'] . '</wrNbr>
								<wrLot>' . $wo['wo_lot'] . '</wrLot>
								<wrOp>' . $wo['operation'] . '</wrOp>
                                <emp>' . $wo['employee'] . '</emp>
								<opQtyComp>' . $wo['qtyComplete'] . '</opQtyComp>';

		// if ($wo['qtyReject'] != 0) {
		//     $qdocRequest .= '<rejects>true</rejects>';
		// }

		// if ($wo['qtyRework'] != 0) {
		//     $qdocRequest .= '<reworks>true</reworks>';
		// }

		$qdocRequest .= '<effDate>' . date('Y-m-d') . '</effDate>';
        if ($operationComplete == 'Yes') {
            $qdocRequest .= '<wocomp>true</wocomp>
                        <move>true</move>
                        <compprev>true</compprev>';
        } else {
            $qdocRequest .= '<wocomp>false</wocomp>
                        <move>true</move>
                        <compprev>false</compprev>';
        }

		$qdocRequest .= '<stopRun>' . $wo['duration'] . '</stopRun>';

		// if ($wo['qtyReject'] != 0) {
		//     $qdocRequest .= '<rejectsDetail>
		//                         <rejqty>' . $wo['qtyReject'] . '</rejqty>
		//                     </rejectsDetail>';
		// }

		// if ($wo['qtyRework'] != 0) {
		//     $qdocRequest .= '<reworksDetail>
		//                         <rwkqty>' . $wo['qtyRework'] . '</rwkqty>
		//                     </reworksDetail>';
		// }

		$qdocRequest .= '<destWkctr>' . $wo['work_center'] . '</destWkctr>';

		$qdocRequest .= '</WOLaborFeedback>
						</dsWOLaborFeedback>
					</recordLaborFeedbackByWO>
				</soapenv:Body>
			</soapenv:Envelope>';

		// dd($qdocRequest);

		$curlOptions = array(
			CURLOPT_URL => $qxUrl,
			CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
			CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
			CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
			CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false
		);

		$getInfo = '';
		$httpCode = 0;
		$curlErrno = 0;
		$curlError = '';


		$qdocResponse = '';

		$curl = curl_init();
		if ($curl) {
			curl_setopt_array($curl, $curlOptions);
			$qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
			//
			$curlErrno = curl_errno($curl);
			$curlError = curl_error($curl);
			$first = true;
			foreach (curl_getinfo($curl) as $key => $value) {
				if (gettype($value) != 'array') {
					if (!$first) $getInfo .= ", ";
					$getInfo = $getInfo . $key . '=>' . $value;
					$first = false;
					if ($key == 'http_code') $httpCode = $value;
				}
			}
			curl_close($curl);
		}

		// dd($qdocResponse);

		if (is_bool($qdocResponse)) {

			DB::rollBack();
			return false;
		}
		$xmlResp = simplexml_load_string($qdocResponse);
		// dd($xmlResp);
		$xmlResp->registerXPathNamespace('ns1', 'urn:schemas-qad-com:xml-services');
		$qdocResult = (string) $xmlResp->xpath('//ns1:result')[0];

		$errorMessage = '';

		if ($qdocResult == 'error') {
			$xmlResp->registerXPathNamespace('ns3', 'urn:schemas-qad-com:xml-services:common');
			$errMsgs = $xmlResp->xpath('//ns3:tt_msg_desc');
			$errorMessage = '';
			foreach ($errMsgs as $err) {
				$errorMessage .= $err;
			}
		}

		return [$qdocResult, $errorMessage];
	}

	public function qxCustomerShipTo($customerShipTo, $domain_name, $operation)
	{
		$qxwsa = qxwsa::where('domain_id', $customerShipTo->domain_id)->first();
		$qxUrl = $qxwsa->qx_url;
		$timeout = 0;
		$receiver = 'risis';

		$qdocRequest = '<?xml version="1.0" encoding="UTF-8"?>
			<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
			xmlns:qcom="urn:schemas-qad-com:xml-services:common"
			xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
			<soapenv:Header>
				<wsa:Action/>
				<wsa:To>urn:services-qad-com:risis</wsa:To>
				<wsa:MessageID>urn:services-qad-com::risis</wsa:MessageID>
				<wsa:ReferenceParameters>
				<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
				</wsa:ReferenceParameters>
				<wsa:ReplyTo>
				<wsa:Address>urn:services-qad-com:</wsa:Address>
				</wsa:ReplyTo>
			</soapenv:Header>
			<soapenv:Body>
				<bdebtorshipto>
				<qcom:dsSessionContext>
					<qcom:ttContext>
					<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
					<qcom:propertyName>domain</qcom:propertyName>
					<qcom:propertyValue>' . $domain_name . '</qcom:propertyValue>
					</qcom:ttContext>
					<qcom:ttContext>
					<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
					<qcom:propertyName>scopeTransaction</qcom:propertyName>
					<qcom:propertyValue>true</qcom:propertyValue>
					</qcom:ttContext>
					<qcom:ttContext>
					<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
					<qcom:propertyName>version</qcom:propertyName>
					<qcom:propertyValue>ERP3_1</qcom:propertyValue>
					</qcom:ttContext>
					<qcom:ttContext>
					<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
					<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
					<qcom:propertyValue>false</qcom:propertyValue>
					</qcom:ttContext>
					<!--
					<qcom:ttContext>
					<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
					<qcom:propertyName>username</qcom:propertyName>
					<qcom:propertyValue/>
					</qcom:ttContext>
					<qcom:ttContext>
					<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
					<qcom:propertyName>password</qcom:propertyName>
					<qcom:propertyValue/>
					</qcom:ttContext>
					-->
					<qcom:ttContext>
					<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
					<qcom:propertyName>action</qcom:propertyName>
					<qcom:propertyValue/>
					</qcom:ttContext>
					<qcom:ttContext>
					<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
					<qcom:propertyName>entity</qcom:propertyName>
					<qcom:propertyValue>' . $domain_name . '</qcom:propertyValue>
					</qcom:ttContext>
					<qcom:ttContext>
					<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
					<qcom:propertyName>email</qcom:propertyName>
					<qcom:propertyValue/>
					</qcom:ttContext>
					<qcom:ttContext>
					<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
					<qcom:propertyName>emailLevel</qcom:propertyName>
					<qcom:propertyValue/>
					</qcom:ttContext>
				</qcom:dsSessionContext>
				<BDebtorShipTo>
					<tContextInfo>
					<!-- <tcAction>text</tcAction> -->
					<tcActivityCode>' . $operation . '</tcActivityCode>
					<tlPartialUpdate>true</tlPartialUpdate>
					</tContextInfo>
					<tDebtorShipTo>
					<DebtorShipToCode>' . $customerShipTo->cst_ship_to_code . '</DebtorShipToCode>
					<DebtorShipToIsDebtor>false</DebtorShipToIsDebtor>
					<tcDebtorCode>' . $customerShipTo->cst_customer_code . '</tcDebtorCode>
					<DebtorShipToName>' . $customerShipTo->cst_ship_to_name . '</DebtorShipToName>
					<IsActive>true</IsActive>
					<tcAddressCity>' . $customerShipTo->cst_address_city . '</tcAddressCity>
					<tcAddressEMail>' . $customerShipTo->cst_email . '</tcAddressEMail>
					<tcAddressFax> ' . $customerShipTo->cst_fax . '</tcAddressFax>
					<tlAddressIsTemporary>' . $customerShipTo->cst_is_temp . '</tlAddressIsTemporary>
					<tcAddressName>' . $customerShipTo->cst_address_name . '</tcAddressName>
					<tcAddressStreet1>' . $customerShipTo->cst_ad_line1 . '</tcAddressStreet1>
					<tcAddressStreet2>' . $customerShipTo->cst_ad_line2 . '</tcAddressStreet2>
					<tcAddressTelephone>' . $customerShipTo->cst_telephone . '</tcAddressTelephone>
					<tcAddressTypeCode>SHIP-TO</tcAddressTypeCode>
					<tcAddressWebSite>' . $customerShipTo->cst_internet . '</tcAddressWebSite>
					<tcAddressZip>' . $customerShipTo->cst_postal . '</tcAddressZip>
					<tcBusinessRelationCode>' . $customerShipTo->cst_customer_code . '</tcBusinessRelationCode>
					<tcCountryCode>' . $customerShipTo->cst_country_code . '</tcCountryCode>
					<tcCountryDescription>' . $customerShipTo->cst_country_desc . '</tcCountryDescription>
					<tcStateDescription/>
					<tcAddressStreet3>' . $customerShipTo->cst_ad_line3 . '</tcAddressStreet3>
					<tcStateCode/>
					<tcDebtorEndUserCode/>
					<tcTxzTaxZone>' . $customerShipTo->cst_tax_zone . '</tcTxzTaxZone>
					<tiAddressFormat>1</tiAddressFormat>';

		if ($customerShipTo->cst_is_taxable == 'Yes') {
			$qdocRequest .= '<tlAddressIsTaxable>true</tlAddressIsTaxable>';
		} else {
			$qdocRequest .= '<tlAddressIsTaxable>false</tlAddressIsTaxable>';
		}

		if ($customerShipTo->cst_is_tax_in_city == 'Yes') {
			$qdocRequest .= '<tlAddressIsTaxInCity>true</tlAddressIsTaxInCity>';
		} else {
			$qdocRequest .= '<tlAddressIsTaxInCity>false</tlAddressIsTaxInCity>';
		}

		$qdocRequest .= '<tcAddressSearchName>' . $customerShipTo->cst_search_name . '</tcAddressSearchName>
					<tcAddressTaxIDState/>
					<tcLngCode>' . $customerShipTo->cst_language_code . '</tcLngCode>';

		if ($customerShipTo->cst_is_tax_included) {
			$qdocRequest .= '<tlAddressIsTaxIncluded>true</tlAddressIsTaxIncluded>';
		} else {
			$qdocRequest .= '<tlAddressIsTaxIncluded>false</tlAddressIsTaxIncluded>';
		}

		$qdocRequest .= '<tiAddressTaxDeclaration>0</tiAddressTaxDeclaration>
					<tcAddressTaxIDFederal/>
					<tcAddressTaxIDMisc1/>
					<tcAddressTaxIDMisc2/>
					<tcAddressTaxIDMisc3/>
					<tcTxclTaxCls/>
					<tcTxuTaxUsage/>
					<tlAddressIsShared>false</tlAddressIsShared>
					<tlUpdateAllSharedAddRecords>false</tlUpdateAllSharedAddRecords>
					</tDebtorShipTo>
				</BDebtorShipTo>
				</bdebtorshipto>
			</soapenv:Body>
			</soapenv:Envelope>';

		$qdocRequest = str_replace('&', '&amp;', $qdocRequest);

		$curlOptions = array(
			CURLOPT_URL => $qxUrl,
			CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
			CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
			CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
			CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false
		);

		$getInfo = '';
		$httpCode = 0;
		$curlErrno = 0;
		$curlError = '';


		$qdocResponse = '';

		$curl = curl_init();
		if ($curl) {
			curl_setopt_array($curl, $curlOptions);
			$qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
			//
			$curlErrno = curl_errno($curl);
			$curlError = curl_error($curl);
			$first = true;
			foreach (curl_getinfo($curl) as $key => $value) {
				if (gettype($value) != 'array') {
					if (!$first) $getInfo .= ", ";
					$getInfo = $getInfo . $key . '=>' . $value;
					$first = false;
					if ($key == 'http_code') $httpCode = $value;
				}
			}
			curl_close($curl);
		}

		if (is_bool($qdocResponse)) {

			DB::rollBack();
			return false;
		}
		$xmlResp = simplexml_load_string($qdocResponse);
		// dd($xmlResp);
		$xmlResp->registerXPathNamespace('ns1', 'urn:schemas-qad-com:xml-services');
		$qdocResult = (string) $xmlResp->xpath('//ns1:result')[0];

		$errorMessage = '';

		if ($qdocResult != 'success' || $qdocResult != 'warning') {
			$xmlResp->registerXPathNamespace('ns3', 'urn:schemas-qad-com:xml-services:common');
			$errMsgs = $xmlResp->xpath('//ns3:tt_msg_desc');
			$errorMessage = '';
			foreach ($errMsgs as $err) {
				$errorMessage .= $err;
			}
		}

		return [$qdocResult, $errorMessage];
	}
	/*
	public function qxPendingInvoice($soNumber, $domain_id)
	{
		$qxwsa = qxwsa::where('domain_id', $domain_id)->first();
		$domain = Domain::where('id', $domain_id)->first();
		$salesOrder = SOMstr::with(['getSODetail'])->where('so_nbr', $soNumber)->first();
		$qxUrl = $qxwsa->qx_url;
		$timeout = 0;
		$receiver = 'risis';
		$qdocHead = '
		<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services" xmlns:qcom="urn:schemas-qad-com:xml-services:common" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
		<soapenv:Header>
		<wsa:Action/>
		<wsa:To>urn:services-qad-com:risis</wsa:To>
		<wsa:MessageID>urn:services-qad-com::risis</wsa:MessageID>
		<wsa:ReferenceParameters>
		<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
		</wsa:ReferenceParameters>
		<wsa:ReplyTo>
		<wsa:Address>urn:services-qad-com:</wsa:Address>
		</wsa:ReplyTo>
		</soapenv:Header>
		<soapenv:Body>
		<maintainPendingInvoice>
		<qcom:dsSessionContext>
		<qcom:ttContext>
		<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
		<qcom:propertyName>domain</qcom:propertyName>
		<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
		</qcom:ttContext>
		<qcom:ttContext>
		<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
		<qcom:propertyName>scopeTransaction</qcom:propertyName>
		<qcom:propertyValue>true</qcom:propertyValue>
		</qcom:ttContext>
		<qcom:ttContext>
		<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
		<qcom:propertyName>version</qcom:propertyName>
		<qcom:propertyValue>eB2_4</qcom:propertyValue>
		</qcom:ttContext>
		<qcom:ttContext>
		<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
		<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
		<qcom:propertyValue>false</qcom:propertyValue>
		</qcom:ttContext>

		<qcom:ttContext>
		<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
		<qcom:propertyName>action</qcom:propertyName>
		<qcom:propertyValue/>
		</qcom:ttContext>
		<qcom:ttContext>
		<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
		<qcom:propertyName>entity</qcom:propertyName>
		<qcom:propertyValue/>
		</qcom:ttContext>
		<qcom:ttContext>
		<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
		<qcom:propertyName>email</qcom:propertyName>
		<qcom:propertyValue/>
		</qcom:ttContext>
		<qcom:ttContext>
		<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
		<qcom:propertyName>emailLevel</qcom:propertyName>
		<qcom:propertyValue/>
		</qcom:ttContext>
		</qcom:dsSessionContext>';
		$qdocBody = '
		<dsPendingInvoice>
			<pendingInvoice>
				<soNbr>' . $salesOrder->so_nbr . '</soNbr>
				<soCust>' . $salesOrder->sold_to . '</soCust>
				<soShip>' . $salesOrder->ship_to . '</soShip>
				<soOrdDate>' . $salesOrder->order_date . '</soOrdDate>
				<soDueDate>' . $salesOrder->need_date . '</soDueDate>
				<soCrTerms>' . $salesOrder->so_ct_code . '</soCrTerms>
				<soCurr>' .$salesOrder->so_currency. '</soCurr>';

				if ($salesOrder->so_fr_list != '') {
					$qdocBody .= '<soFrList>' . $salesOrder->so_fr_list . '</soFrList>
								<soFrTerms>' . $salesOrder->so_fr_terms . '</soFrTerms>
								<calcFr>true</calcFr>';
				}

				$qdocBody .= '<soTaxc1>' . $salesOrder->so_taxc . '</soTaxc1>';
				if ($salesOrder->so_is_taxable == 'Yes') {
					$qdocBody .= '<soTaxable1>true</soTaxable1>';
				} else {
					$qdocBody .= '<soTaxable1>false</soTaxable1>';
				}

				// if ($salesOrder->so_is_taxin == 'Yes') {
				// 	$qdocBody .= '<taxIn>true</taxIn>';
				// } else {
				// 	$qdocBody .= '<taxIn>false</taxIn>';
				// }

				$qdocBody .= '<soSlspsn>' . $salesOrder->so_sales_person . '</soSlspsn>
										<soRmks>' . $salesOrder->remark . '</soRmks>
										<soPo>' . $salesOrder->so_po . '</soPo>';

				if ($salesOrder->comment != '') {
                    if (strpos($salesOrder->comment, "\r\n") !== false) {
                        $commentHeader = explode("\r\n", $salesOrder->comment);
                    }
					$qdocBody .= '<pendingInvoiceTransComment>
						<cmtSeq>1</cmtSeq>
						<cdRef>' . $salesOrder->sold_to . '</cdRef>
						<cdType></cdType>
						<cdLang></cdLang>';

					foreach ($commentHeader as $key => $commentHead) {
						if ($key < 15) {
							$qdocBody .= '<cmtCmmt>' . $commentHead . '</cmtCmmt>';
						}
					}

					$qdocBody .= '</pendingInvoiceTransComment>';
				}

				foreach ($salesOrder->getSODetail as $soDetail) {
					$qdocBody .= '<salesLine>
									<line>' . $soDetail->line_detail . '</line>
									<sodPart>' . $soDetail->item_code . '</sodPart>
									<sodQtyChg>' . $soDetail->qty_order . '</sodQtyChg>
									<sodListPr>' . $soDetail->list_price . '</sodListPr>
									<discount>' . $soDetail->item_discount . '</discount>
									<sodLoc>' . $soDetail->sod_loc . '</sodLoc>';
					if ($soDetail->sod_is_taxable == 'Yes') {
						$qdocBody .= '<sodTaxable>true</sodTaxable>';
					} else {
						$qdocBody .= '<sodTaxable>false</sodTaxable>';
					}

					if ($soDetail->sod_tax_in == 'Yes') {
						$qdocBody .= '<sodTaxIn>true</sodTaxIn>';
					} else {
						$qdocBody .= '<sodTaxIn>false</sodTaxIn>';
					}
					$qdocBody .= '<sodTaxc>' . $soDetail->sod_taxc . '</sodTaxc>
										<sodUm>' . $soDetail->item_um . '</sodUm>';


					if ($soDetail->comment_det != '') {
						$qdocBody .= '<sodcmmts>true</sodcmmts>';
						$commentDetail = str_split($soDetail->comment_det, 76);
						$qdocBody .= '<salesLineTransComment>
							<cmtSeq>1</cmtSeq>
							<cdRef>' . $soDetail->item_code . '</cdRef>
							<cdType></cdType>
							<cdLang></cdLang>';

						foreach ($commentDetail as $cmtDet => $cmtDetail) {
							if ($cmtDet < 15) {
								$qdocBody .= '<cmtCmmt>' . $cmtDetail . '</cmtCmmt>';
							}
						}
						$qdocBody .= '</salesLineTransComment>';
					}

					$qdocBody .= '</salesLine>';
				}
				$qdocBody .= '<soTrl1Amt>' . $salesOrder->so_trl_amt_1 . '</soTrl1Amt>';
				$qdocBody .= '<soTrl2Amt>' . $salesOrder->so_trl_amt_2 . '</soTrl2Amt>';
				$qdocBody .= '<soTrl3Amt>' . $salesOrder->so_trl_amt_3 . '</soTrl3Amt>';

		$qdocFooter = '
		</pendingInvoice>
		</dsPendingInvoice>
		</maintainPendingInvoice>
		</soapenv:Body>
		</soapenv:Envelope> ';

		$qdocRequest = $qdocHead . $qdocBody . $qdocFooter;

		$qdocResponse = '';
		$curlOptions = array(
			CURLOPT_URL => $qxUrl,
			CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
			CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
			CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
			CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false
		);

		$getInfo = '';
		$httpCode = 0;
		$curlErrno = 0;
		$curlError = '';


		$qdocResponse = '';

		$curl = curl_init();
		if ($curl) {
			curl_setopt_array($curl, $curlOptions);
			$qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
			//
			$curlErrno = curl_errno($curl);
			$curlError = curl_error($curl);
			$first = true;
			foreach (curl_getinfo($curl) as $key => $value) {
				if (gettype($value) != 'array') {
					if (!$first) $getInfo .= ", ";
					$getInfo = $getInfo . $key . '=>' . $value;
					$first = false;
					if ($key == 'http_code') $httpCode = $value;
				}
			}
			curl_close($curl);
		}



		if (is_bool($qdocResponse)) {

			DB::rollBack();
			return false;
		}
		$xmlResp = simplexml_load_string($qdocResponse);
		// dd($xmlResp);
		$xmlResp->registerXPathNamespace('ns1', 'urn:schemas-qad-com:xml-services');
		$qdocResult = (string) $xmlResp->xpath('//ns1:result')[0];

		$errorMessage = '';

		if ($qdocResult != 'success' || $qdocResult != 'warning') {
			$xmlResp->registerXPathNamespace('ns3', 'urn:schemas-qad-com:xml-services:common');
			$errMsgs = $xmlResp->xpath('//ns3:tt_msg_desc');
			$errorMessage = '';
			foreach ($errMsgs as $err) {
				$errorMessage .= $err;
			}
		}

		return [$qdocResult, $errorMessage];
	}
	*/
	public function qxPOReceive($poNumber, $domain_id)
	{
		$qxwsa = qxwsa::where('domain_id', $domain_id)->first();
		$domain = Domain::where('id', $domain_id)->first();
		$purchaseOrder = POMstr::withoutGlobalScopes()->with(['getPODet'])->where('po_nbr', $poNumber)->first();
		$qxUrl = $qxwsa->qx_url;
		$timeout = 0;
		$receiver = 'risis';
		$qdochead = '<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services" xmlns:qcom="urn:schemas-qad-com:xml-services:common" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
			<soapenv:Header>
			<wsa:Action/>
			<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
			<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
			<wsa:ReferenceParameters>
			<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
			</wsa:ReferenceParameters>
			<wsa:ReplyTo>
			<wsa:Address>urn:services-qad-com:</wsa:Address>
			</wsa:ReplyTo>
			</soapenv:Header>
			<soapenv:Body>
			<receivePurchaseOrder>
			<qcom:dsSessionContext>
			<qcom:ttContext>
			<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
			<qcom:propertyName>domain</qcom:propertyName>
			<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
			</qcom:ttContext>
			<qcom:ttContext>
			<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
			<qcom:propertyName>scopeTransaction</qcom:propertyName>
			<qcom:propertyValue>false</qcom:propertyValue>
			</qcom:ttContext>
			<qcom:ttContext>
			<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
			<qcom:propertyName>version</qcom:propertyName>
			<qcom:propertyValue>ERP3_3</qcom:propertyValue>
			</qcom:ttContext>
			<qcom:ttContext>
			<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
			<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
			<qcom:propertyValue>false</qcom:propertyValue>
			</qcom:ttContext>

			<qcom:ttContext>
			<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
			<qcom:propertyName>action</qcom:propertyName>
			<qcom:propertyValue/>
			</qcom:ttContext>
			<qcom:ttContext>
			<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
			<qcom:propertyName>entity</qcom:propertyName>
			<qcom:propertyValue/>
			</qcom:ttContext>
			<qcom:ttContext>
			<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
			<qcom:propertyName>email</qcom:propertyName>
			<qcom:propertyValue/>
			</qcom:ttContext>
			<qcom:ttContext>
			<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
			<qcom:propertyName>emailLevel</qcom:propertyName>
			<qcom:propertyValue/>
			</qcom:ttContext>
			</qcom:dsSessionContext>';

		$qdocBody = '
			<dsPurchaseOrderReceive>
			<purchaseOrderReceive>

			<ordernum>' . $purchaseOrder->po_nbr . '</ordernum>';
		foreach ($purchaseOrder->getPODet as $podet) {
			$qdocBody .= '
				<lineDetail>
				<line>' . $podet->pod_line . '</line>
				<lotserialQty>' . $podet->pod_qty_ord . '</lotserialQty>
				<location>ERB</location>
				</lineDetail>
				';
		}
		$qdocBody .= '
			</purchaseOrderReceive>
			</dsPurchaseOrderReceive>';
		/*$qdocBody = '
			<dsPurchaseOrderReceive>
			<purchaseOrderReceive>

			<ordernum>'.$purchaseOrder->po_nbr.'</ordernum>
			<fillAll>true</fillAll>
			</purchaseOrderReceive>
			</dsPurchaseOrderReceive>'; */
		$qdocFooter = '</receivePurchaseOrder>
					</soapenv:Body>
					</soapenv:Envelope>';
		$qdocRequest = $qdochead . $qdocBody . $qdocFooter;
		$curlOptions = array(
			CURLOPT_URL => $qxUrl,
			CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
			CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
			CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
			CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false
		);

		$getInfo = '';
		$httpCode = 0;
		$curlErrno = 0;
		$curlError = '';


		$qdocResponse = '';

		$curl = curl_init();
		if ($curl) {
			curl_setopt_array($curl, $curlOptions);
			$qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
			//
			$curlErrno = curl_errno($curl);
			$curlError = curl_error($curl);
			$first = true;
			foreach (curl_getinfo($curl) as $key => $value) {
				if (gettype($value) != 'array') {
					if (!$first) $getInfo .= ", ";
					$getInfo = $getInfo . $key . '=>' . $value;
					$first = false;
					if ($key == 'http_code') $httpCode = $value;
				}
			}
			curl_close($curl);
		}

		if (is_bool($qdocResponse)) {

			DB::rollBack();
			return false;
		}
		$xmlResp = simplexml_load_string($qdocResponse);
		// dd($xmlResp);
		$xmlResp->registerXPathNamespace('ns1', 'urn:schemas-qad-com:xml-services');
		$qdocResult = (string) $xmlResp->xpath('//ns1:result')[0];

		$errorMessage = '';

		if ($qdocResult != 'success' || $qdocResult != 'warning') {
			$xmlResp->registerXPathNamespace('ns3', 'urn:schemas-qad-com:xml-services:common');
			$errMsgs = $xmlResp->xpath('//ns3:tt_msg_desc');
			$errorMessage = '';
			foreach ($errMsgs as $err) {
				$errorMessage .= $err;
			}
		}

		return [$qdocResult, $errorMessage];
	}

	public function qxPOReceiveERB($poNumber, $domain_id)
	{
		$qxwsa = qxwsa::where('domain_id', $domain_id)->first();
		$domain = Domain::where('id', $domain_id)->first();
		$purchaseOrder = POMstr::withoutGlobalScopes()->with(['getPODet'])->where('po_domain', $domain->id)->where('po_nbr', $poNumber)->first();
		$qxUrl = $qxwsa->qx_url;
		$timeout = 0;
		$receiver = 'risis';
		$qdochead = '<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services" xmlns:qcom="urn:schemas-qad-com:xml-services:common" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
			<soapenv:Header>
			<wsa:Action/>
			<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
			<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
			<wsa:ReferenceParameters>
			<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
			</wsa:ReferenceParameters>
			<wsa:ReplyTo>
			<wsa:Address>urn:services-qad-com:</wsa:Address>
			</wsa:ReplyTo>
			</soapenv:Header>
			<soapenv:Body>
			<receivePurchaseOrder>
			<qcom:dsSessionContext>
			<qcom:ttContext>
			<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
			<qcom:propertyName>domain</qcom:propertyName>
			<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
			</qcom:ttContext>
			<qcom:ttContext>
			<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
			<qcom:propertyName>scopeTransaction</qcom:propertyName>
			<qcom:propertyValue>false</qcom:propertyValue>
			</qcom:ttContext>
			<qcom:ttContext>
			<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
			<qcom:propertyName>version</qcom:propertyName>
			<qcom:propertyValue>ERP3_3</qcom:propertyValue>
			</qcom:ttContext>
			<qcom:ttContext>
			<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
			<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
			<qcom:propertyValue>false</qcom:propertyValue>
			</qcom:ttContext>

			<qcom:ttContext>
			<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
			<qcom:propertyName>action</qcom:propertyName>
			<qcom:propertyValue/>
			</qcom:ttContext>
			<qcom:ttContext>
			<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
			<qcom:propertyName>entity</qcom:propertyName>
			<qcom:propertyValue/>
			</qcom:ttContext>
			<qcom:ttContext>
			<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
			<qcom:propertyName>email</qcom:propertyName>
			<qcom:propertyValue/>
			</qcom:ttContext>
			<qcom:ttContext>
			<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
			<qcom:propertyName>emailLevel</qcom:propertyName>
			<qcom:propertyValue/>
			</qcom:ttContext>
			</qcom:dsSessionContext>';
		$qdocBody = '
			<dsPurchaseOrderReceive>
			<purchaseOrderReceive>

			<ordernum>' . $purchaseOrder->po_nbr . '</ordernum>';
		foreach ($purchaseOrder->getPODet as $podet) {
			$qdocBody .= '
				<lineDetail>
				<line>' . $podet->pod_line . '</line>
				<lotserialQty>' . $podet->pod_qty_ord . '</lotserialQty>
				<location>ERB</location>
				</lineDetail>
				';
		}
		$qdocBody .= '
			</purchaseOrderReceive>
			</dsPurchaseOrderReceive>';
		$qdocFooter = '</receivePurchaseOrder>
			</soapenv:Body>
			</soapenv:Envelope>';
		/* <fillAll>true</fillAll>*/
		$qdocRequest = $qdochead . $qdocBody . $qdocFooter;
		$curlOptions = array(
			CURLOPT_URL => $qxUrl,
			CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
			CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
			CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
			CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false
		);

		$getInfo = '';
		$httpCode = 0;
		$curlErrno = 0;
		$curlError = '';


		$qdocResponse = '';

		$curl = curl_init();
		if ($curl) {
			curl_setopt_array($curl, $curlOptions);
			$qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
			//
			$curlErrno = curl_errno($curl);
			$curlError = curl_error($curl);
			$first = true;
			foreach (curl_getinfo($curl) as $key => $value) {
				if (gettype($value) != 'array') {
					if (!$first) $getInfo .= ", ";
					$getInfo = $getInfo . $key . '=>' . $value;
					$first = false;
					if ($key == 'http_code') $httpCode = $value;
				}
			}
			curl_close($curl);
		}

		if (is_bool($qdocResponse)) {

			DB::rollBack();
			return false;
		}
		$xmlResp = simplexml_load_string($qdocResponse);
		// dd($xmlResp);
		$xmlResp->registerXPathNamespace('ns1', 'urn:schemas-qad-com:xml-services');
		$qdocResult = (string) $xmlResp->xpath('//ns1:result')[0];

		$errorMessage = '';

		if ($qdocResult != 'success' || $qdocResult != 'warning') {
			$xmlResp->registerXPathNamespace('ns3', 'urn:schemas-qad-com:xml-services:common');
			$errMsgs = $xmlResp->xpath('//ns3:tt_msg_desc');
			$errorMessage = '';
			foreach ($errMsgs as $err) {
				$errorMessage .= $err;
			}
		}

		return [$qdocResult, $errorMessage];
	}

	public function qxPendingInvoiceERB($soNumber, $domain_id)
	{
		$qxwsa = qxwsa::where('domain_id', $domain_id)->first();
		$domain = Domain::where('id', $domain_id)->first();
		$salesOrder = SOMstr::with(['getSODetail'])->where('so_nbr', $soNumber)->first();
		$qxUrl = $qxwsa->qx_url;
		$timeout = 0;
		$receiver = 'risis';
		$qdocHead = '
		<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services" xmlns:qcom="urn:schemas-qad-com:xml-services:common" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
		<soapenv:Header>
		<wsa:Action/>
		<wsa:To>urn:services-qad-com:risis</wsa:To>
		<wsa:MessageID>urn:services-qad-com::risis</wsa:MessageID>
		<wsa:ReferenceParameters>
		<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
		</wsa:ReferenceParameters>
		<wsa:ReplyTo>
		<wsa:Address>urn:services-qad-com:</wsa:Address>
		</wsa:ReplyTo>
		</soapenv:Header>
		<soapenv:Body>
		<maintainPendingInvoice>
		<qcom:dsSessionContext>
		<qcom:ttContext>
		<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
		<qcom:propertyName>domain</qcom:propertyName>
		<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
		</qcom:ttContext>
		<qcom:ttContext>
		<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
		<qcom:propertyName>scopeTransaction</qcom:propertyName>
		<qcom:propertyValue>true</qcom:propertyValue>
		</qcom:ttContext>
		<qcom:ttContext>
		<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
		<qcom:propertyName>version</qcom:propertyName>
		<qcom:propertyValue>eB2_4</qcom:propertyValue>
		</qcom:ttContext>
		<qcom:ttContext>
		<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
		<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
		<qcom:propertyValue>false</qcom:propertyValue>
		</qcom:ttContext>

		<qcom:ttContext>
		<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
		<qcom:propertyName>action</qcom:propertyName>
		<qcom:propertyValue/>
		</qcom:ttContext>
		<qcom:ttContext>
		<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
		<qcom:propertyName>entity</qcom:propertyName>
		<qcom:propertyValue/>
		</qcom:ttContext>
		<qcom:ttContext>
		<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
		<qcom:propertyName>email</qcom:propertyName>
		<qcom:propertyValue/>
		</qcom:ttContext>
		<qcom:ttContext>
		<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
		<qcom:propertyName>emailLevel</qcom:propertyName>
		<qcom:propertyValue/>
		</qcom:ttContext>
		</qcom:dsSessionContext>';
		$qdocBody = '
		<dsPendingInvoice>
			<pendingInvoice>
				<soNbr>' . $salesOrder->so_nbr . '</soNbr>
				<soCust>' . $salesOrder->sold_to . '</soCust>
				<soShip>' . $salesOrder->ship_to . '</soShip>
				<soOrdDate>' . $salesOrder->order_date . '</soOrdDate>
				<soDueDate>' . $salesOrder->need_date . '</soDueDate>
				<soCrTerms>' . $salesOrder->so_ct_code . '</soCrTerms>
				<soCurr>' . $salesOrder->so_currency . '</soCurr>';

		if ($salesOrder->so_fr_list != '') {
			$qdocBody .= '<soFrList>' . $salesOrder->so_fr_list . '</soFrList>
								<soFrTerms>' . $salesOrder->so_fr_terms . '</soFrTerms>
								<calcFr>true</calcFr>';
		}

		$qdocBody .= '<soTaxc1>' . $salesOrder->so_taxc . '</soTaxc1>';
		if ($salesOrder->so_is_taxable == 'Yes') {
			$qdocBody .= '<soTaxable1>true</soTaxable1>';
		} else {
			$qdocBody .= '<soTaxable1>false</soTaxable1>';
		}

		// if ($salesOrder->so_is_taxin == 'Yes') {
		// 	$qdocBody .= '<taxIn>true</taxIn>';
		// } else {
		// 	$qdocBody .= '<taxIn>false</taxIn>';
		// }

		$qdocBody .= '<soSlspsn>' . $salesOrder->so_sales_person . '</soSlspsn>
										<soRmks>' . $salesOrder->remark . '</soRmks>
										<soPo>' . $salesOrder->so_po . '</soPo>';

		if ($salesOrder->comment != '') {

			if (strpos($salesOrder->comment, "\r\n") !== false) {
				$commentHeader = explode("\r\n", $salesOrder->comment);
				foreach ($commentHeader as $key => $commentHead) {
					if ($key < 15) {
						$qdocBody .= '<cmtCmmt>' . $commentHead . '</cmtCmmt>';
					}
				}
			} else {

				$qdocBody .= '<cmtCmmt>' . $salesOrder->comment . '</cmtCmmt>';
			}

			$qdocBody .= '<pendingInvoiceTransComment>
						<cmtSeq>1</cmtSeq>
						<cdRef>' . $salesOrder->sold_to . '</cdRef>
						<cdType></cdType>
						<cdLang></cdLang>';



			$qdocBody .= '</pendingInvoiceTransComment>';
		}

		foreach ($salesOrder->getSODetail as $soDetail) {
			$qdocBody .= '<salesLine>
									<line>' . $soDetail->line_detail . '</line>
									<sodPart>' . $soDetail->item_code . '</sodPart>
									<sodQtyChg>' . $soDetail->qty_order . '</sodQtyChg>
									<sodListPr>' . $soDetail->item_net_price . '</sodListPr>
									<discount>' . $soDetail->item_discount . '</discount>
									<sodPrice>' . $soDetail->item_net_price . '</sodPrice>
									<sodLoc>' . $soDetail->sod_loc . '</sodLoc>';
			if ($soDetail->sod_is_taxable == 'Yes') {
				$qdocBody .= '<sodTaxable>true</sodTaxable>';
			} else {
				$qdocBody .= '<sodTaxable>false</sodTaxable>';
			}

			if ($soDetail->sod_tax_in == 'Yes') {
				$qdocBody .= '<sodTaxIn>true</sodTaxIn>';
			} else {
				$qdocBody .= '<sodTaxIn>false</sodTaxIn>';
			}
			$qdocBody .= '<sodTaxc>' . $soDetail->sod_taxc . '</sodTaxc>
										<sodUm>' . $soDetail->item_um . '</sodUm>';


			if ($soDetail->comment_det != '') {
				$qdocBody .= '<sodcmmts>true</sodcmmts>';
				$commentDetail = str_split($soDetail->comment_det, 76);
				$qdocBody .= '<salesLineTransComment>
							<cmtSeq>1</cmtSeq>
							<cdRef>' . $soDetail->item_code . '</cdRef>
							<cdType></cdType>
							<cdLang></cdLang>';

				foreach ($commentDetail as $cmtDet => $cmtDetail) {
					if ($cmtDet < 15) {
						$qdocBody .= '<cmtCmmt>' . $cmtDetail . '</cmtCmmt>';
					}
				}
				$qdocBody .= '</salesLineTransComment>';
			}

			$qdocBody .= '</salesLine>';
		}
		$qdocBody .= '<soTrl1Amt>' . $salesOrder->so_trl_amt_1 . '</soTrl1Amt>';
		$qdocBody .= '<soTrl2Amt>' . $salesOrder->so_trl_amt_2 . '</soTrl2Amt>';
		$qdocBody .= '<soTrl3Amt>' . $salesOrder->so_trl_amt_3 . '</soTrl3Amt>';

		$qdocFooter = '
		</pendingInvoice>
		</dsPendingInvoice>
		</maintainPendingInvoice>
		</soapenv:Body>
		</soapenv:Envelope> ';

		$qdocRequest = $qdocHead . $qdocBody . $qdocFooter;
		$qdocResponse = '';
		$curlOptions = array(
			CURLOPT_URL => $qxUrl,
			CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
			CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
			CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
			CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false
		);

		$getInfo = '';
		$httpCode = 0;
		$curlErrno = 0;
		$curlError = '';


		$qdocResponse = '';

		$curl = curl_init();
		if ($curl) {
			curl_setopt_array($curl, $curlOptions);
			$qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
			//
			$curlErrno = curl_errno($curl);
			$curlError = curl_error($curl);
			$first = true;
			foreach (curl_getinfo($curl) as $key => $value) {
				if (gettype($value) != 'array') {
					if (!$first) $getInfo .= ", ";
					$getInfo = $getInfo . $key . '=>' . $value;
					$first = false;
					if ($key == 'http_code') $httpCode = $value;
				}
			}
			curl_close($curl);
		}



		if (is_bool($qdocResponse)) {

			DB::rollBack();
			return false;
		}
		$xmlResp = simplexml_load_string($qdocResponse);
		// dd($xmlResp);
		$xmlResp->registerXPathNamespace('ns1', 'urn:schemas-qad-com:xml-services');
		$qdocResult = (string) $xmlResp->xpath('//ns1:result')[0];

		$errorMessage = '';

		if ($qdocResult != 'success' || $qdocResult != 'warning') {
			$xmlResp->registerXPathNamespace('ns3', 'urn:schemas-qad-com:xml-services:common');
			$errMsgs = $xmlResp->xpath('//ns3:tt_msg_desc');
			$errorMessage = '';
			foreach ($errMsgs as $err) {
				$errorMessage .= $err;
			}
		}

		return [$qdocResult, $errorMessage];
	}

	public function qxPurchaseOrderERB($domain_id, $poNumber, $qxtendAction)
	{
		$qxwsa = qxwsa::where('domain_id', $domain_id)->first();
		$domain = Domain::where('id', $domain_id)->first();
		$poMaster = POMstr::withoutGlobalScopes()->with(['getPODet'])->where('po_domain', $domain_id)
			->where('po_nbr', $poNumber)->first();

		// dd($poMaster);

		$qxUrl = $qxwsa->qx_url;
		$timeout = 0;
		$receiver = 'risis';

		switch ($qxtendAction) {
			case 'create':
				// XML Qxtend
				$qdocHead = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>
						<maintainPurchaseOrder>
						<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>eB2_3</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>';

				$qdocBody = '<dsPurchaseOrder>
								<purchaseOrder>
									<poNbr>' . $poMaster->po_nbr . '</poNbr>
									<poVend>' . $poMaster->po_supplier . '</poVend>
									<poShip>' . $poMaster->po_shipto . '</poShip>
									<poOrdDate>' . $poMaster->po_ord_date . '</poOrdDate>
									<poDueDate>' . $poMaster->po_due_date . '</poDueDate>
									<poRmks>' . $poMaster->po_remark . '</poRmks>
									<poSite>' . $poMaster->po_site . '</poSite>
									<poTaxable>' . $poMaster->getPODet[0]->pod_taxable . '</poTaxable>
									<poTaxc>' . $poMaster->getPODet[0]->pod_tax_class . '</poTaxc>
									<poCrTerms>' . $poMaster->getPODet[0]->pod_cr_terms . '</poCrTerms>';
				if ($poMaster->po_contract != null) {
					$qdocBody .= '<poContract>' . $poMaster->po_contract . '</poContract>';
				}




				foreach ($poMaster->getPODet as $poDetail) {
					$needSub = 'true';
					$needCC = 'true';
					$needProject = 'false';
					$qdocBody .= '<lineDetail>
							<line>' . $poDetail->pod_line . '</line>
							<podPart>' . $poDetail->pod_item_code . '</podPart>';

					if ($poDetail->pod_is_memo == 'Yes') {
						$qdocBody .= '<desc1>' . $poDetail->pod_item_desc . '</desc1>';
					}
					$qdocBody .= '<podQtyOrd>' . $poDetail->pod_qty_ord . '</podQtyOrd>
							<podUm>' . $poDetail->pod_um . '</podUm>
							<podDueDate>' . $poDetail->pod_due_date . '</podDueDate>
							<podNeed>' . $poDetail->pod_need_date . '</podNeed>
                            <podPurCost>' . $poDetail->pod_pur_cost . '</podPurCost>
							<podTaxable>' . $poDetail->pod_taxable . '</podTaxable>
							<podTaxc>' . $poDetail->pod_tax_class . '</podTaxc>
							<podLoc>ERB</podLoc>';

					if ($poDetail->pod_is_memo == 'Yes' and $poDetail->pod_account != null) {
						$qdocBody .= '<podAcct>' . $poDetail->pod_account . '</podAcct>';

						// WSA to check sub account, cost center, project
						$wsaAccountChilds = (new WSAServices())->wsaAccountChild($poMaster->po_domain, $poDetail->pod_account);
						if ($wsaAccountChilds[0] == 'true') {
							$needSub = $wsaAccountChilds[1][0]->t_need_sub;
							$needCC = $wsaAccountChilds[1][0]->t_need_cc;
							$needProject = $wsaAccountChilds[1][0]->t_need_project;
						}
					}

					if ($needSub == 'true') {
						$qdocBody .= '<podSub>' . $poDetail->pod_sub_acc . '</podSub>';
					}

					if ($needCC == 'true') {
						$qdocBody .= '<podCc>' . $poDetail->pod_cost_center . '</podCc>';
					}

					if ($needProject == 'true') {
						$qdocBody .= '<podProject>' . $poDetail->pod_project . '</podProject>';
					}
					if ($poDetail->pod_comment) {
						$qdocBody .= '<podcmmts>true</podcmmts>
						<lineDetailTransComment>
						<cmtSeq>1</cmtSeq>
						<cdSeq>1</cdSeq>
						<cmtCmmt>' . $poDetail->pod_comment . '</cmtCmmt>
						</lineDetailTransComment>';
					}

					$qdocBody .= '</lineDetail>';
				}
				$qdocFooter = '</purchaseOrder>
							</dsPurchaseOrder>
						</maintainPurchaseOrder>
					</soapenv:Body>
				</soapenv:Envelope>';

				$qdocRequest = $qdocHead . $qdocBody . $qdocFooter;
				// dd($qdocRequest);
				break;

			case 'update':
				// XML Qxtend
				$qdocHead = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>
						<maintainPurchaseOrder>
						<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>eB2_3</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>';

				$qdocBody = '<dsPurchaseOrder>
								<purchaseOrder>
									<operation>M</operation>
									<poNbr>' . $poMaster->po_nbr . '</poNbr>
									<poVend>' . $poMaster->po_supplier . '</poVend>
									<poShip>' . $poMaster->po_shipto . '</poShip>
									<poOrdDate>' . $poMaster->po_ord_date . '</poOrdDate>
									<poDueDate>' . $poMaster->po_due_date . '</poDueDate>
									<poRmks>' . $poMaster->po_remark . '</poRmks>
									<poSite>' . $poMaster->po_site . '</poSite>
									<poRev>' . $poMaster->po_rev . '</poRev>';

				foreach ($poMaster->getPODet as $poDetail) {
					if ($poDetail->pod_deleted == 'No') {
						$qdocBody .= '<lineDetail>
							<line>' . $poDetail->pod_line . '</line>
							<podPart>' . $poDetail->pod_item_code . '</podPart>
							<podQtyOrd>' . $poDetail->pod_qty_ord . '</podQtyOrd>
							<podUm>' . $poDetail->pod_um . '</podUm>
							<podDueDate>' . $poDetail->pod_due_date . '</podDueDate>
							<podNeed>' . $poDetail->pod_need_date . '</podNeed>
							<podPurCost>' . $poDetail->pod_pur_cost . '</podPurCost>
							<podSub>' . $poDetail->pod_sub_acc . '</podSub>
							<podCc>' . $poDetail->pod_cost_center . '</podCc>
							<podProject>' . $poDetail->pod_project . '</podProject>';
						if ($poDetail->pod_comment) {
							$qdocBody .= '<podcmmts>true</podcmmts>
								<lineDetailTransComment>
								<cmtSeq>1</cmtSeq>
								<cdSeq>1</cdSeq>
								<cmtCmmt>' . $poDetail->pod_comment . '</cmtCmmt>
								</lineDetailTransComment>';
						}
						$qdocBody .= '</lineDetail>';
					} else {
						$qdocBody .= '<lineDetail>
							<operation>R</operation>
							<line>' . $poDetail->pod_line . '</line>
							</lineDetail>';
					}
				}
				$qdocFooter = '</purchaseOrder>
							</dsPurchaseOrder>
						</maintainPurchaseOrder>
					</soapenv:Body>
				</soapenv:Envelope>';

				$qdocRequest = $qdocHead . $qdocBody . $qdocFooter;
				break;

			case 'remove':
				// XML Qxtend
				$qdocHead = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>
						<maintainPurchaseOrder>
						<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>eB2_3</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>';

				$qdocBody = '<dsPurchaseOrder>
								<purchaseOrder>
									<operation>R</operation>
									<poNbr>' . $poMaster->po_nbr . '</poNbr>';
				$qdocFooter = '</purchaseOrder>
							</dsPurchaseOrder>
						</maintainPurchaseOrder>
					</soapenv:Body>
				</soapenv:Envelope>';

				$qdocRequest = $qdocHead . $qdocBody . $qdocFooter;
				break;
		}

		$curlOptions = array(
			CURLOPT_URL => $qxUrl,
			CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
			CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
			CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
			CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false
		);

		$getInfo = '';
		$httpCode = 0;
		$curlErrno = 0;
		$curlError = '';


		$qdocResponse = '';

		$curl = curl_init();
		if ($curl) {
			curl_setopt_array($curl, $curlOptions);
			$qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
			//
			$curlErrno = curl_errno($curl);
			$curlError = curl_error($curl);
			$first = true;
			foreach (curl_getinfo($curl) as $key => $value) {
				if (gettype($value) != 'array') {
					if (!$first) $getInfo .= ", ";
					$getInfo = $getInfo . $key . '=>' . $value;
					$first = false;
					if ($key == 'http_code') $httpCode = $value;
				}
			}
			curl_close($curl);
		}


		// dd($qdocRequest,$qdocResponse);
		if (is_bool($qdocResponse)) {

			DB::rollBack();
			return false;
		}
		$xmlResp = simplexml_load_string($qdocResponse);

		$xmlResp->registerXPathNamespace('ns1', 'urn:schemas-qad-com:xml-services');
		$qdocResult = (string) $xmlResp->xpath('//ns1:result')[0];

		$errorMessage = '';

		if ($qdocResult != 'success' || $qdocResult != 'warning') {
			$xmlResp->registerXPathNamespace('ns3', 'urn:schemas-qad-com:xml-services:common');
			$errMsgs = $xmlResp->xpath('//ns3:tt_msg_desc');
			$errorMessage = '';
			foreach ($errMsgs as $err) {
				$errorMessage .= $err;
			}
		}

		return [$qdocResult, $errorMessage];
	}

	public function qxPurchaseOrderService($domain_id, $poNumber, $qxtendAction)
	{
		$qxwsa = qxwsa::where('domain_id', $domain_id)->first();
		$domain = Domain::where('id', $domain_id)->first();
		$poMaster = POMstr::withoutGlobalScopes()->with(['getPODet'])->where('po_domain', $domain_id)
			->where('po_nbr', $poNumber)->first();

		// dd($poNumber, $poMaster);

		$qxUrl = $qxwsa->qx_url;
		$timeout = 0;
		$receiver = 'risis';

		switch ($qxtendAction) {
			case 'create':
				// XML Qxtend
				$qdocHead = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>
						<maintainPurchaseOrder>
						<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>eB2_3</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>';

				$qdocBody = '<dsPurchaseOrder>
								<purchaseOrder>
									<poNbr>' . $poMaster->po_nbr . '</poNbr>
									<poVend>' . $poMaster->po_supplier . '</poVend>
									<poShip>' . $poMaster->po_shipto . '</poShip>
									<poOrdDate>' . $poMaster->po_ord_date . '</poOrdDate>
									<poDueDate>' . $poMaster->po_due_date . '</poDueDate>
									<poRmks>' . $poMaster->po_remark . '</poRmks>
									<poSite>' . $poMaster->po_site . '</poSite>
									<poTaxable>' . $poMaster->getPODet[0]->pod_taxable . '</poTaxable>
									<poTaxc>' . $poMaster->getPODet[0]->pod_tax_class . '</poTaxc>
									<poCrTerms>' . $poMaster->getPODet[0]->pod_cr_terms . '</poCrTerms>';
				if ($poMaster->po_contract != null) {
					$qdocBody .= '<poContract>' . $poMaster->po_contract . '</poContract>';
				}




				foreach ($poMaster->getPODet as $poDetail) {
					$needSub = 'true';
					$needCC = 'true';
					$needProject = 'false';
					$qdocBody .= '<lineDetail>
							<line>' . $poDetail->pod_line . '</line>
							<podPart>' . $poDetail->pod_item_code . '</podPart>';

					if ($poDetail->pod_is_memo == 'Yes') {
						$qdocBody .= '<desc1>' . $poDetail->pod_item_desc . '</desc1>';
					}
					$qdocBody .= '<podQtyOrd>' . $poDetail->pod_qty_ord . '</podQtyOrd>
							<podUm>' . $poDetail->pod_um . '</podUm>
							<podDueDate>' . $poDetail->pod_due_date . '</podDueDate>
							<podNeed>' . $poDetail->pod_need_date . '</podNeed>
                            <podPurCost>' . $poDetail->pod_pur_cost . '</podPurCost>
							<podTaxable>' . $poDetail->pod_taxable . '</podTaxable>
							<podTaxc>' . $poDetail->pod_tax_class . '</podTaxc>';

					if ($poDetail->pod_is_memo == 'Yes' and $poDetail->pod_account != null) {
						$qdocBody .= '<podAcct>' . $poDetail->pod_account . '</podAcct>';

						// WSA to check sub account, cost center, project
						$wsaAccountChilds = (new WSAServices())->wsaAccountChild($poMaster->po_domain, $poDetail->pod_account);
						if ($wsaAccountChilds[0] == 'true') {
							$needSub = $wsaAccountChilds[1][0]->t_need_sub;
							$needCC = $wsaAccountChilds[1][0]->t_need_cc;
							$needProject = $wsaAccountChilds[1][0]->t_need_project;
						}
					}

					if ($needSub == 'true') {
						$qdocBody .= '<podSub>' . $poDetail->pod_sub_acc . '</podSub>';
					}

					if ($needCC == 'true') {
						$qdocBody .= '<podCc>' . $poDetail->pod_cost_center . '</podCc>';
					}

					if ($needProject == 'true') {
						$qdocBody .= '<podProject>' . $poDetail->pod_project . '</podProject>';
					}
					if ($poDetail->pod_comment) {
						$qdocBody .= '<podcmmts>true</podcmmts>
						<lineDetailTransComment>
						<cmtSeq>1</cmtSeq>
						<cdSeq>1</cdSeq>
						<cmtCmmt>' . $poDetail->pod_comment . '</cmtCmmt>
						</lineDetailTransComment>';
					}

					$qdocBody .= '</lineDetail>';
				}
				$qdocFooter = '</purchaseOrder>
							</dsPurchaseOrder>
						</maintainPurchaseOrder>
					</soapenv:Body>
				</soapenv:Envelope>';

				$qdocRequest = $qdocHead . $qdocBody . $qdocFooter;
				// dd($qdocRequest);
				break;

			case 'update':
				// XML Qxtend
				$qdocHead = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>
						<maintainPurchaseOrder>
						<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>eB2_3</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>';

				$qdocBody = '<dsPurchaseOrder>
								<purchaseOrder>
									<operation>M</operation>
									<poNbr>' . $poMaster->po_nbr . '</poNbr>
									<poVend>' . $poMaster->po_supplier . '</poVend>
									<poShip>' . $poMaster->po_shipto . '</poShip>
									<poOrdDate>' . $poMaster->po_ord_date . '</poOrdDate>
									<poDueDate>' . $poMaster->po_due_date . '</poDueDate>
									<poRmks>' . $poMaster->po_remark . '</poRmks>
									<poSite>' . $poMaster->po_site . '</poSite>
									<poRev>' . $poMaster->po_rev . '</poRev>';

				foreach ($poMaster->getPODet as $poDetail) {
					if ($poDetail->pod_deleted == 'No') {
						$qdocBody .= '<lineDetail>
							<line>' . $poDetail->pod_line . '</line>
							<podPart>' . $poDetail->pod_item_code . '</podPart>
							<podQtyOrd>' . $poDetail->pod_qty_ord . '</podQtyOrd>
							<podUm>' . $poDetail->pod_um . '</podUm>
							<podDueDate>' . $poDetail->pod_due_date . '</podDueDate>
							<podNeed>' . $poDetail->pod_need_date . '</podNeed>
							<podPurCost>' . $poDetail->pod_pur_cost . '</podPurCost>
							<podSub>' . $poDetail->pod_sub_acc . '</podSub>
							<podCc>' . $poDetail->pod_cost_center . '</podCc>
							<podProject>' . $poDetail->pod_project . '</podProject>';
						if ($poDetail->pod_comment) {
							$qdocBody .= '<podcmmts>true</podcmmts>
								<lineDetailTransComment>
								<cmtSeq>1</cmtSeq>
								<cdSeq>1</cdSeq>
								<cmtCmmt>' . $poDetail->pod_comment . '</cmtCmmt>
								</lineDetailTransComment>';
						}
						$qdocBody .= '</lineDetail>';
					} else {
						$qdocBody .= '<lineDetail>
							<operation>R</operation>
							<line>' . $poDetail->pod_line . '</line>
							</lineDetail>';
					}
				}
				$qdocFooter = '</purchaseOrder>
							</dsPurchaseOrder>
						</maintainPurchaseOrder>
					</soapenv:Body>
				</soapenv:Envelope>';

				$qdocRequest = $qdocHead . $qdocBody . $qdocFooter;
				break;

			case 'remove':
				// XML Qxtend
				$qdocHead = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>
						<maintainPurchaseOrder>
						<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>eB2_3</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>';

				$qdocBody = '<dsPurchaseOrder>
								<purchaseOrder>
									<operation>R</operation>
									<poNbr>' . $poMaster->po_nbr . '</poNbr>';
				$qdocFooter = '</purchaseOrder>
							</dsPurchaseOrder>
						</maintainPurchaseOrder>
					</soapenv:Body>
				</soapenv:Envelope>';

				$qdocRequest = $qdocHead . $qdocBody . $qdocFooter;
				break;
		}

		$curlOptions = array(
			CURLOPT_URL => $qxUrl,
			CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
			CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
			CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
			CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false
		);

		$getInfo = '';
		$httpCode = 0;
		$curlErrno = 0;
		$curlError = '';


		$qdocResponse = '';

		$curl = curl_init();
		if ($curl) {
			curl_setopt_array($curl, $curlOptions);
			$qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
			//
			$curlErrno = curl_errno($curl);
			$curlError = curl_error($curl);
			$first = true;
			foreach (curl_getinfo($curl) as $key => $value) {
				if (gettype($value) != 'array') {
					if (!$first) $getInfo .= ", ";
					$getInfo = $getInfo . $key . '=>' . $value;
					$first = false;
					if ($key == 'http_code') $httpCode = $value;
				}
			}
			curl_close($curl);
		}


		// dd($qdocRequest,$qdocResponse);
		if (is_bool($qdocResponse)) {

			DB::rollBack();
			return false;
		}
		$xmlResp = simplexml_load_string($qdocResponse);

		$xmlResp->registerXPathNamespace('ns1', 'urn:schemas-qad-com:xml-services');
		$qdocResult = (string) $xmlResp->xpath('//ns1:result')[0];

		$errorMessage = '';

		if ($qdocResult != 'success' || $qdocResult != 'warning') {
			$xmlResp->registerXPathNamespace('ns3', 'urn:schemas-qad-com:xml-services:common');
			$errMsgs = $xmlResp->xpath('//ns3:tt_msg_desc');
			$errorMessage = '';
			foreach ($errMsgs as $err) {
				$errorMessage .= $err;
			}
		}

		return [$qdocResult, $errorMessage];
	}
	public function qxSalesOrderService($domain_id, $soNumber, $action)
	{
		$qxwsa = qxwsa::where('domain_id', $domain_id)->first();
		$domain = Domain::where('id', $domain_id)->first();
		$salesOrder = SOMstr::with(['getSODetail'])->where('so_nbr', $soNumber)->first();
		$qxUrl = $qxwsa->qx_url;
		$timeout = 0;
		$receiver = 'risis';

		switch ($action) {
			case 'create':
				// XML Qxtend
				$qdocHead = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>
						<maintainSalesOrder>
						<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>ERP3_2</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>';

				$qdocBody = '<dsSalesOrder>
								<salesOrder>
									<soNbr>' . $salesOrder->so_nbr . '</soNbr>
									<soCust>' . $salesOrder->sold_to . '</soCust>
									<soShip>' . $salesOrder->ship_to . '</soShip>
									<soOrdDate>' . $salesOrder->order_date . '</soOrdDate>
									<soDueDate>' . $salesOrder->need_date . '</soDueDate>
									<soCrTerms>' . $salesOrder->so_ct_code . '</soCrTerms>';


				if ($salesOrder->so_fr_list != '') {
					$qdocBody .= '<soFrList>' . $salesOrder->so_fr_list . '</soFrList>
								<soFrTerms>' . $salesOrder->so_fr_terms . '</soFrTerms>
								<calcFr>true</calcFr>';
				}

				$qdocBody .= '<soCurr>' . $salesOrder->so_currency . '</soCurr>
                    <soTaxc1>' . $salesOrder->so_taxc . '</soTaxc1>';
				if ($salesOrder->so_is_taxable == 'Yes') {
					$qdocBody .= '<soTaxable1>true</soTaxable1>';
				} else {
					$qdocBody .= '<soTaxable1>false</soTaxable1>';
				}

				// if ($salesOrder->so_is_taxin == 'Yes') {
				// 	$qdocBody .= '<taxIn>true</taxIn>';
				// } else {
				// 	$qdocBody .= '<taxIn>false</taxIn>';
				// }

				$qdocBody .= '<soSlspsn>' . $salesOrder->so_sales_person . '</soSlspsn>
										<soRmks>' . $salesOrder->remark . '</soRmks>
										<soPo>' . $salesOrder->so_po . '</soPo>';

				if ($salesOrder->comment != '') {
					if (strpos($salesOrder->comment, "\r\n") !== false) {
						$commentHeader = explode("\r\n", $salesOrder->comment);
					}
					$qdocBody .= '<salesOrderTransComment>
						<cmtSeq>1</cmtSeq>
						<cdRef>' . $salesOrder->sold_to . '</cdRef>
						<cdType></cdType>
						<cdLang></cdLang>';

					foreach ($commentHeader as $key => $commentHead) {
						if ($key < 15) {
							$qdocBody .= '<cmtCmmt>' . $commentHead . '</cmtCmmt>';
						}
					}

					$qdocBody .= '</salesOrderTransComment>';
				}

				foreach ($salesOrder->getSODetail as $soDetail) {
					$qdocBody .= '<salesOrderDetail>
									<line>' . $soDetail->line_detail . '</line>
									<sodPart>' . $soDetail->item_code . '</sodPart>
									<sodQtyOrd>' . $soDetail->qty_order . '</sodQtyOrd>
									<sodListPr>' . $soDetail->list_price . '</sodListPr>
									<discount>' . $soDetail->item_discount . '</discount>
                                    <sodPrice>' . $soDetail->item_net_price . '</sodPrice>
									<sodLoc>' . $soDetail->sod_loc . '</sodLoc>';
					if ($soDetail->sod_is_taxable == 'Yes') {
						$qdocBody .= '<sodTaxable>true</sodTaxable>';
					} else {
						$qdocBody .= '<sodTaxable>false</sodTaxable>';
					}

					if ($soDetail->sod_tax_in == 'Yes') {
						$qdocBody .= '<sodTaxIn>true</sodTaxIn>';
					} else {
						$qdocBody .= '<sodTaxIn>false</sodTaxIn>';
					}
					$qdocBody .= '<sodTaxc>' . $soDetail->sod_taxc . '</sodTaxc>
										<sodUm>' . $soDetail->item_um . '</sodUm>';


					if ($soDetail->comment_det != '') {
						$qdocBody .= '<sodcmmts>true</sodcmmts>';
						$commentDetail = str_split($soDetail->comment_det, 76);
						$qdocBody .= '<salesOrderDetailTransComment>
							<cmtSeq>1</cmtSeq>
							<cdRef>' . $soDetail->item_code . '</cdRef>
							<cdType></cdType>
							<cdLang></cdLang>';

						foreach ($commentDetail as $cmtDet => $cmtDetail) {
							if ($cmtDet < 15) {
								$qdocBody .= '<cmtCmmt>' . $cmtDetail . '</cmtCmmt>';
							}
						}
						$qdocBody .= '</salesOrderDetailTransComment>';
					}

					$qdocBody .= '</salesOrderDetail>';
				}
				$qdocBody .= '<soTrl1Amt>' . $salesOrder->so_trl_amt_1 . '</soTrl1Amt>';
				$qdocBody .= '<soTrl2Amt>' . $salesOrder->so_trl_amt_2 . '</soTrl2Amt>';
				$qdocBody .= '<soTrl3Amt>' . $salesOrder->so_trl_amt_3 . '</soTrl3Amt>';
				$qdocFooter = '</salesOrder>
							</dsSalesOrder>
						</maintainSalesOrder>
					</soapenv:Body>
				</soapenv:Envelope>';
				break;

			case 'update':
				// XML Qxtend
				$qdocHead = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>
						<maintainSalesOrder>
						<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>ERP3_2</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>';


				$qdocBody = '<dsSalesOrder>
								<salesOrder>
									<operation>M</operation>
									<soNbr>' . $salesOrder->so_nbr . '</soNbr>
									<soCust>' . $salesOrder->sold_to . '</soCust>
									<soShip>' . $salesOrder->ship_to . '</soShip>
									<soOrdDate>' . $salesOrder->order_date . '</soOrdDate>
									<soDueDate>' . $salesOrder->need_date . '</soDueDate>
									<soCrTerms>' . $salesOrder->so_ct_code . '</soCrTerms>
									<soTaxc1>' . $salesOrder->so_taxc . '</soTaxc1>';
				if ($salesOrder->so_is_taxable == 'Yes') {
					$qdocBody .= '<soTaxable1>true</soTaxable1>';
				} else {
					$qdocBody .= '<soTaxable1>false</soTaxable1>';
				}

				if ($salesOrder->so_is_taxin == 'Yes') {
					$qdocBody .= '<taxIn>true</taxIn>';
				} else {
					$qdocBody .= '<taxIn>false</taxIn>';
				}

				$qdocBody .= '<soSlspsn>' . $salesOrder->so_sales_person . '</soSlspsn>
										<soRmks>' . $salesOrder->remark . '</soRmks>
										<soPo>' . $salesOrder->so_nbr . '</soPo>';

				foreach ($salesOrder->getSODetail as $soDetail) {
					if ($soDetail->line_deleted == 'No') {
						$qdocBody .= '<salesOrderDetail>
									<line>' . $soDetail->line_detail . '</line>
									<sodPart>' . $soDetail->item_code . '</sodPart>
									<sodQtyOrd>' . $soDetail->qty_order . '</sodQtyOrd>
									<sodListPr>' . $soDetail->list_price . '</sodListPr>
									<discount>' . $soDetail->item_discount . '</discount>
									<sodLoc>' . $soDetail->sod_loc . '</sodLoc>';
						if ($soDetail->sod_is_taxable == 'Yes') {
							$qdocBody .= '<sodTaxable>true</sodTaxable>';
						} else {
							$qdocBody .= '<sodTaxable>false</sodTaxable>';
						}
						$qdocBody .= '<sodTaxc>' . $soDetail->sod_taxc . '</sodTaxc>
							<sodUm>' . $soDetail->item_um . '</sodUm>
							</salesOrderDetail>';
					} else {
						$qdocBody .= '<salesOrderDetail>
									<operation>R</operation>
									<line>' . $soDetail->line_detail . '</line>
							</salesOrderDetail>';
					}
				}
				$qdocFooter = '</salesOrder>
							</dsSalesOrder>
						</maintainSalesOrder>
					</soapenv:Body>
				</soapenv:Envelope>';
				break;

			case 'delete':
				// XML Qxtend
				$qdocHead = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>
						<maintainSalesOrder>
						<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>ERP3_2</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>';

				$qdocBody = '<dsSalesOrder>
								<salesOrder>
									<operation>R</operation>
									<soNbr>' . $soNumber . '</soNbr>';
				$qdocFooter = '</salesOrder>
							</dsSalesOrder>
						</maintainSalesOrder>
					</soapenv:Body>
				</soapenv:Envelope>';
				break;
		}

		$qdocRequest = $qdocHead . $qdocBody . $qdocFooter;
		// dd($qdocRequest);

		$curlOptions = array(
			CURLOPT_URL => $qxUrl,
			CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
			CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
			CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
			CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false
		);

		$getInfo = '';
		$httpCode = 0;
		$curlErrno = 0;
		$curlError = '';


		$qdocResponse = '';

		$curl = curl_init();
		if ($curl) {
			curl_setopt_array($curl, $curlOptions);
			$qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
			//
			$curlErrno = curl_errno($curl);
			$curlError = curl_error($curl);
			$first = true;
			foreach (curl_getinfo($curl) as $key => $value) {
				if (gettype($value) != 'array') {
					if (!$first) $getInfo .= ", ";
					$getInfo = $getInfo . $key . '=>' . $value;
					$first = false;
					if ($key == 'http_code') $httpCode = $value;
				}
			}
			curl_close($curl);
		}

		// dd($qdocResponse);

		if (is_bool($qdocResponse)) {

			DB::rollBack();
			return false;
		}
		$xmlResp = simplexml_load_string($qdocResponse);
		// dd($xmlResp);
		$xmlResp->registerXPathNamespace('ns1', 'urn:schemas-qad-com:xml-services');
		$qdocResult = (string) $xmlResp->xpath('//ns1:result')[0];

		$errorMessage = '';

		if ($qdocResult != 'success' || $qdocResult != 'warning') {
			$xmlResp->registerXPathNamespace('ns3', 'urn:schemas-qad-com:xml-services:common');
			$errMsgs = $xmlResp->xpath('//ns3:tt_msg_desc');
			$errorMessage = '';
			foreach ($errMsgs as $err) {
				$errorMessage .= $err;
			}
		}

		return [$qdocResult, $errorMessage];
	}
	public function qxSalesOrderERB($domain_id, $soNumber, $action)
	{
		$qxwsa = qxwsa::where('domain_id', $domain_id)->first();
		$domain = Domain::where('id', $domain_id)->first();
		$salesOrder = SOMstr::with(['getSODetail'])->where('so_nbr', $soNumber)->first();
		$qxUrl = $qxwsa->qx_url;
		$timeout = 0;
		$receiver = 'risis';

		switch ($action) {
			case 'create':
				// XML Qxtend
				$qdocHead = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>
						<maintainSalesOrder>
						<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>ERP3_2</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>';

				$qdocBody = '<dsSalesOrder>
								<salesOrder>
									<soNbr>' . $salesOrder->so_nbr . '</soNbr>
									<soCust>' . $salesOrder->sold_to . '</soCust>
									<soShip>' . $salesOrder->ship_to . '</soShip>
									<soOrdDate>' . $salesOrder->order_date . '</soOrdDate>
									<soDueDate>' . $salesOrder->need_date . '</soDueDate>
									<soCrTerms>' . $salesOrder->so_ct_code . '</soCrTerms>';


				if ($salesOrder->so_fr_list != '') {
					$qdocBody .= '<soFrList>' . $salesOrder->so_fr_list . '</soFrList>
								<soFrTerms>' . $salesOrder->so_fr_terms . '</soFrTerms>
								<calcFr>true</calcFr>';
				}

				$qdocBody .= '<soCurr>' . $salesOrder->so_currency . '</soCurr>
                    <soTaxc1>' . $salesOrder->so_taxc . '</soTaxc1>';
				if ($salesOrder->so_is_taxable == 'Yes') {
					$qdocBody .= '<soTaxable1>true</soTaxable1>';
				} else {
					$qdocBody .= '<soTaxable1>false</soTaxable1>';
				}

				// if ($salesOrder->so_is_taxin == 'Yes') {
				// 	$qdocBody .= '<taxIn>true</taxIn>';
				// } else {
				// 	$qdocBody .= '<taxIn>false</taxIn>';
				// }

				$qdocBody .= '<soSlspsn>' . $salesOrder->so_sales_person . '</soSlspsn>
										<soRmks>' . $salesOrder->remark . '</soRmks>
										<soPo>' . $salesOrder->so_po . '</soPo>';

				if ($salesOrder->comment != '') {
					if (strpos($salesOrder->comment, "\r\n") !== false) {
						$commentHeader = explode("\r\n", $salesOrder->comment);
					}
					$qdocBody .= '<salesOrderTransComment>
						<cmtSeq>1</cmtSeq>
						<cdRef>' . $salesOrder->sold_to . '</cdRef>
						<cdType></cdType>
						<cdLang></cdLang>';

					foreach ($commentHeader as $key => $commentHead) {
						if ($key < 15) {
							$qdocBody .= '<cmtCmmt>' . $commentHead . '</cmtCmmt>';
						}
					}

					$qdocBody .= '</salesOrderTransComment>';
				}

				foreach ($salesOrder->getSODetail as $soDetail) {
					$qdocBody .= '<salesOrderDetail>
									<line>' . $soDetail->line_detail . '</line>
									<sodPart>' . $soDetail->item_code . '</sodPart>
									<sodQtyOrd>' . $soDetail->qty_order . '</sodQtyOrd>
									<sodListPr>' . $soDetail->item_net_price . '</sodListPr>
									<discount>' . $soDetail->item_discount . '</discount>
                                    <sodPrice>' . $soDetail->item_net_price . '</sodPrice>
									<sodLoc>' . $soDetail->sod_loc . '</sodLoc>';
					if ($soDetail->sod_is_taxable == 'Yes') {
						$qdocBody .= '<sodTaxable>true</sodTaxable>';
					} else {
						$qdocBody .= '<sodTaxable>false</sodTaxable>';
					}

					if ($soDetail->sod_tax_in == 'Yes') {
						$qdocBody .= '<sodTaxIn>true</sodTaxIn>';
					} else {
						$qdocBody .= '<sodTaxIn>false</sodTaxIn>';
					}
					$qdocBody .= '<sodTaxc>' . $soDetail->sod_taxc . '</sodTaxc>
										<sodUm>' . $soDetail->item_um . '</sodUm>';


					if ($soDetail->comment_det != '') {
						$qdocBody .= '<sodcmmts>true</sodcmmts>';
						$commentDetail = str_split($soDetail->comment_det, 76);
						$qdocBody .= '<salesOrderDetailTransComment>
							<cmtSeq>1</cmtSeq>
							<cdRef>' . $soDetail->item_code . '</cdRef>
							<cdType></cdType>
							<cdLang></cdLang>';

						foreach ($commentDetail as $cmtDet => $cmtDetail) {
							if ($cmtDet < 15) {
								$qdocBody .= '<cmtCmmt>' . $cmtDetail . '</cmtCmmt>';
							}
						}
						$qdocBody .= '</salesOrderDetailTransComment>';
					}

					$qdocBody .= '</salesOrderDetail>';
				}
				$qdocBody .= '<soTrl1Amt>' . $salesOrder->so_trl_amt_1 . '</soTrl1Amt>';
				$qdocBody .= '<soTrl2Amt>' . $salesOrder->so_trl_amt_2 . '</soTrl2Amt>';
				$qdocBody .= '<soTrl3Amt>' . $salesOrder->so_trl_amt_3 . '</soTrl3Amt>';
				$qdocFooter = '</salesOrder>
							</dsSalesOrder>
						</maintainSalesOrder>
					</soapenv:Body>
				</soapenv:Envelope>';
				break;

			case 'update':
				// XML Qxtend
				$qdocHead = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>
						<maintainSalesOrder>
						<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>ERP3_2</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>';


				$qdocBody = '<dsSalesOrder>
								<salesOrder>
									<operation>M</operation>
									<soNbr>' . $salesOrder->so_nbr . '</soNbr>
									<soCust>' . $salesOrder->sold_to . '</soCust>
									<soShip>' . $salesOrder->ship_to . '</soShip>
									<soOrdDate>' . $salesOrder->order_date . '</soOrdDate>
									<soDueDate>' . $salesOrder->need_date . '</soDueDate>
									<soCrTerms>' . $salesOrder->so_ct_code . '</soCrTerms>
									<soTaxc1>' . $salesOrder->so_taxc . '</soTaxc1>';
				if ($salesOrder->so_is_taxable == 'Yes') {
					$qdocBody .= '<soTaxable1>true</soTaxable1>';
				} else {
					$qdocBody .= '<soTaxable1>false</soTaxable1>';
				}

				if ($salesOrder->so_is_taxin == 'Yes') {
					$qdocBody .= '<taxIn>true</taxIn>';
				} else {
					$qdocBody .= '<taxIn>false</taxIn>';
				}

				$qdocBody .= '<soSlspsn>' . $salesOrder->so_sales_person . '</soSlspsn>
										<soRmks>' . $salesOrder->remark . '</soRmks>
										<soPo>' . $salesOrder->so_nbr . '</soPo>';

				foreach ($salesOrder->getSODetail as $soDetail) {
					if ($soDetail->line_deleted == 'No') {
						$qdocBody .= '<salesOrderDetail>
									<line>' . $soDetail->line_detail . '</line>
									<sodPart>' . $soDetail->item_code . '</sodPart>
									<sodQtyOrd>' . $soDetail->qty_order . '</sodQtyOrd>
									<sodListPr>' . $soDetail->list_price . '</sodListPr>
									<discount>' . $soDetail->item_discount . '</discount>
									<sodLoc>' . $soDetail->sod_loc . '</sodLoc>';
						if ($soDetail->sod_is_taxable == 'Yes') {
							$qdocBody .= '<sodTaxable>true</sodTaxable>';
						} else {
							$qdocBody .= '<sodTaxable>false</sodTaxable>';
						}
						$qdocBody .= '<sodTaxc>' . $soDetail->sod_taxc . '</sodTaxc>
							<sodUm>' . $soDetail->item_um . '</sodUm>
							</salesOrderDetail>';
					} else {
						$qdocBody .= '<salesOrderDetail>
									<operation>R</operation>
									<line>' . $soDetail->line_detail . '</line>
							</salesOrderDetail>';
					}
				}
				$qdocFooter = '</salesOrder>
							</dsSalesOrder>
						</maintainSalesOrder>
					</soapenv:Body>
				</soapenv:Envelope>';
				break;

			case 'delete':
				// XML Qxtend
				$qdocHead = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>
						<maintainSalesOrder>
						<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>ERP3_2</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>';

				$qdocBody = '<dsSalesOrder>
								<salesOrder>
									<operation>R</operation>
									<soNbr>' . $soNumber . '</soNbr>';
				$qdocFooter = '</salesOrder>
							</dsSalesOrder>
						</maintainSalesOrder>
					</soapenv:Body>
				</soapenv:Envelope>';
				break;
		}

		$qdocRequest = $qdocHead . $qdocBody . $qdocFooter;
		// dd($qdocRequest);

		$curlOptions = array(
			CURLOPT_URL => $qxUrl,
			CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
			CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
			CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
			CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false
		);

		$getInfo = '';
		$httpCode = 0;
		$curlErrno = 0;
		$curlError = '';


		$qdocResponse = '';

		$curl = curl_init();
		if ($curl) {
			curl_setopt_array($curl, $curlOptions);
			$qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
			//
			$curlErrno = curl_errno($curl);
			$curlError = curl_error($curl);
			$first = true;
			foreach (curl_getinfo($curl) as $key => $value) {
				if (gettype($value) != 'array') {
					if (!$first) $getInfo .= ", ";
					$getInfo = $getInfo . $key . '=>' . $value;
					$first = false;
					if ($key == 'http_code') $httpCode = $value;
				}
			}
			curl_close($curl);
		}

		// dd($qdocResponse);

		if (is_bool($qdocResponse)) {

			DB::rollBack();
			return false;
		}
		$xmlResp = simplexml_load_string($qdocResponse);
		// dd($xmlResp);
		$xmlResp->registerXPathNamespace('ns1', 'urn:schemas-qad-com:xml-services');
		$qdocResult = (string) $xmlResp->xpath('//ns1:result')[0];

		$errorMessage = '';

		if ($qdocResult != 'success' || $qdocResult != 'warning') {
			$xmlResp->registerXPathNamespace('ns3', 'urn:schemas-qad-com:xml-services:common');
			$errMsgs = $xmlResp->xpath('//ns3:tt_msg_desc');
			$errorMessage = '';
			foreach ($errMsgs as $err) {
				$errorMessage .= $err;
			}
		}

		return [$qdocResult, $errorMessage];
	}
	public function qxInvoicePrint($soNumber, $domain_id)
	{
		// $currentdomain = $domain;

		$qxwsa = qxwsa::where('domain_id', $domain_id)->first();
		$domain = Domain::where('id', $domain_id)->first();
		$somstr = SOMstr::where('domain_id', $domain_id)->where('so_nbr', $soNumber)->first();
		// $salesOrder = SOMstr::with(['getSODetail'])->where('so_nbr', $soNumber)->first();
		$qxUrl = $qxwsa->qx_url;
		$timeout = 0;
		$receiver = 'QADERP';

		$qdocHead = '<?xml version="1.0" encoding="UTF-8"?>
					<soapenv:Envelope xmlns="urn:schemas-qad-com:xml-services"
					xmlns:qcom="urn:schemas-qad-com:xml-services:common"
					xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsa="http://www.w3.org/2005/08/addressing">
					<soapenv:Header>
						<wsa:Action/>
						<wsa:To>urn:services-qad-com:' . $receiver . '</wsa:To>
						<wsa:MessageID>urn:services-qad-com::' . $receiver . '</wsa:MessageID>
						<wsa:ReferenceParameters>
						<qcom:suppressResponseDetail>true</qcom:suppressResponseDetail>
						</wsa:ReferenceParameters>
						<wsa:ReplyTo>
						<wsa:Address>urn:services-qad-com:</wsa:Address>
						</wsa:ReplyTo>
					</soapenv:Header>
					<soapenv:Body>
						<InvoicePostandPrint>
						<qcom:dsSessionContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>domain</qcom:propertyName>
							<qcom:propertyValue>' . $domain->domain . '</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>scopeTransaction</qcom:propertyName>
							<qcom:propertyValue>true</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>version</qcom:propertyName>
							<qcom:propertyValue>cust_1</qcom:propertyValue>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>mnemonicsRaw</qcom:propertyName>
							<qcom:propertyValue>false</qcom:propertyValue>
							</qcom:ttContext>
							<!--
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>username</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>password</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							-->
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>action</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>entity</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>email</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
							<qcom:ttContext>
							<qcom:propertyQualifier>QAD</qcom:propertyQualifier>
							<qcom:propertyName>emailLevel</qcom:propertyName>
							<qcom:propertyValue/>
							</qcom:ttContext>
						</qcom:dsSessionContext>';
		$qdocBody = '<dsInvoicePostPrint>
						<InvoicePostPrint>
							<nbr>' . $soNumber . '</nbr>
							<nbr1>' . $soNumber . '</nbr1>
							<shipdate></shipdate>
							<shipdate1></shipdate1>
							<daybookset></daybookset>
							<daybookset1></daybookset1>
							<cust></cust>
							<cust1></cust1>
							<bill></bill>
							<bill1></bill1>
							<ship></ship>
							<ship1></ship1>
							<site></site>
							<site1></site1>
							<lang></lang>
							<lang1></lang1>
							<effDate>' . date_format($somstr->created_at, 'Y-m-d') . '</effDate>
							<printGlDetail>false</printGlDetail>
							<errorsummary>false</errorsummary>
							<incinv>true</incinv>
							<incmemo>true</incmemo>
							<conso>false</conso>
							<inccor>false</inccor>
							<prtCor>false</prtCor>
							<printInvoice>false</printInvoice>
							<lPrtinstbase>false</lPrtinstbase>
							<dev>/i/1</dev>
							<dev2>/i/1</dev2>
							<batchId></batchId>';
		$qdocFooter = '
							</InvoicePostPrint>
							</dsInvoicePostPrint>
							</InvoicePostandPrint>
							</soapenv:Body>
							</soapenv:Envelope>';
		$qdocRequest = $qdocHead . $qdocBody . $qdocFooter;
		// dd($qdocRequest);

		$curlOptions = array(
			CURLOPT_URL => $qxUrl,
			CURLOPT_CONNECTTIMEOUT => $timeout,        // in seconds, 0 = unlimited / wait indefinitely.
			CURLOPT_TIMEOUT => $timeout + 120, // The maximum number of seconds to allow cURL functions to execute. must be greater than CURLOPT_CONNECTTIMEOUT
			CURLOPT_HTTPHEADER => $this->httpHeader($qdocRequest),
			CURLOPT_POSTFIELDS => preg_replace("/\s+/", " ", $qdocRequest),
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false
		);

		$getInfo = '';
		$httpCode = 0;
		$curlErrno = 0;
		$curlError = '';


		$qdocResponse = '';

		$curl = curl_init();
		if ($curl) {
			curl_setopt_array($curl, $curlOptions);
			$qdocResponse = curl_exec($curl);           // sending qdocRequest here, the result is qdocResponse.
			//
			$curlErrno = curl_errno($curl);
			$curlError = curl_error($curl);
			$first = true;
			foreach (curl_getinfo($curl) as $key => $value) {
				if (gettype($value) != 'array') {
					if (!$first) $getInfo .= ", ";
					$getInfo = $getInfo . $key . '=>' . $value;
					$first = false;
					if ($key == 'http_code') $httpCode = $value;
				}
			}
			curl_close($curl);
		}



		if (is_bool($qdocResponse)) {

			DB::rollBack();
			return false;
		}
		$xmlResp = simplexml_load_string($qdocResponse);
		// dd($xmlResp);
		$xmlResp->registerXPathNamespace('ns1', 'urn:schemas-qad-com:xml-services');

		$qdocResult = (string) $xmlResp->xpath('//ns1:result')[0];

		$errorMessage = '';

		if ($qdocResult != 'success') {
			$xmlResp->registerXPathNamespace('ns3', 'urn:schemas-qad-com:xml-services:common');
			$errMsgs = $xmlResp->xpath('//ns3:tt_msg_desc');
			$errorMessage = '';
			foreach ($errMsgs as $err) {
				$errorMessage .= $err;
			}
		}

		return [$qdocResult, $errorMessage];
	}
}
