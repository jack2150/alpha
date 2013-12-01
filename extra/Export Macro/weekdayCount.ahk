Loop
{
    if (a_index > 6) {
		break  ; Terminate the loop
	}
	if (a_index > 5) {
		; double click the up date
		ImageSearch, FoundX, FoundY, 1600, 100, A_ScreenWidth, 200, *2 upButton.bmp
		MouseClick, left, FoundX, FoundY, 2
		
		MouseMove, 1740, 170
		
		; wait for 0.5 sec
		Sleep, 500
	}
	if (a_index > 0 and a_index < 6) {
		; run the export macro
		#Include exportMacro.ahk
	}
}



	