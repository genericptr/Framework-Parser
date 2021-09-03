FPC=/usr/local/lib/fpc/3.3.1/ppcx64
OUTPUT=__output
OPTS=-vbr -dMACOSALL -FU${OUTPUT}

all:
	mkdir -p ${OUTPUT}
	${FPC} CocoaAll.pas -dCOCOAALL ${OPTS}