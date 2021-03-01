ZIPFILE = Plugin.zip
BUILD_TARGET = ./

.PHONY: all
all: clean
	cd src && zip -r ../$(ZIPFILE) $(BUILD_TARGET)

.PHONY: clean
clean:
	rm -f $(ZIPFILE)
