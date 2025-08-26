define input parameter inpdomain as character no-undo.
define input parameter inpsite as character no-undo.
define input parameter inppart as character no-undo.
define input parameter inplot like xxinv_lot no-undo.
define input parameter inpbin as character no-undo.
define input parameter inpwrh as character no-undo.
define input parameter inplevel as character no-undo.

define output parameter outOK as logical no-undo initial false.
define output parameter outMsg as character no-undo initial "".

define variable lvc_partdesc as character no-undo.

define temp-table temp
field t_domain as char
field t_inv_part as char
field t_inv_part_desc as char
field t_inv_loc as char
field t_inv_lot as char
field t_inv_bin as char
field t_inv_level as char
field t_inv_site as char
field t_inv_wrh as char
field t_inv_qty_pick as decimal
field t_inv_qtyoh as decimal
field t_is_prioritize as char /* Untuk Diisi di Web, dibandingkan untuk prioritas */
.

define output parameter table for temp.

for each xxinv_det where xxinv_domain = inpdomain and
                        (if inpsite = "" then true else xxinv_site = inpsite) and
						(if inppart = "" then true else xxinv_part = inppart) and
                        (if inplot = "" then true else xxinv_lot = inplot) and
						(if inpbin = "" then true else xxinv_bin = inpbin) and
						(if inpwrh = "" then true else xxinv_wrh = inpwrh) and
						(if inplevel = "" then true else xxinv_level = inplevel)
						no-lock.

	find first pt_mstr where pt_domain = xxinv_domain and pt_part = xxinv_part no-lock no-error.
	if avail pt_mstr then
		lvc_partdesc = pt_desc1 + pt_desc2.

	outOK = yes.
		create temp.
		assign
		t_domain = inpdomain
		t_inv_part = xxinv_part
		t_inv_part_desc = lvc_partdesc
		t_inv_loc = xxinv_loc
		t_inv_lot = xxinv_lot
		t_inv_bin = xxinv_bin
		t_inv_level = xxinv_level
		t_inv_site = xxinv_site
		t_inv_wrh = xxinv_wrh
		t_inv_qty_pick = xxinv_qty_pick
		t_inv_qtyoh = xxinv_qtyoh
		t_is_prioritize = '0'.
end.


catch eSysError as Progress.Lang.SysError:
    outMsg = eSysError:GetMessage(1).
    delete object eSysError.
end catch.
