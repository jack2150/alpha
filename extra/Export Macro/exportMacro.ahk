; Move the mouse to a new position:
CoordMode Pixel  

; click the up date button
ImageSearch, FoundX, FoundY, 1500, 100, A_ScreenWidth, 200, *2 upButton.bmp
MouseClick, left, FoundX, FoundY

; wait for 1 sec
Sleep, 1000

; click the export button
ImageSearch, FoundX, FoundY, 1650, 80, A_ScreenWidth, 150, *2 exportButton.bmp
MouseClick, left, FoundX, FoundY

; click the export word
ImageSearch, FoundX, FoundY, 1650, 100, A_ScreenWidth, 300, *2 exportWord.bmp
MouseClick, left, FoundX, FoundY

; press enter to export data
Send {Enter}

; wait for 0.5 sec
Sleep, 500
