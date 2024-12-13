# Set-ExecutionPolicy -Scope CurrentUser RemoteSigned
# Navigate to the current directory
cd $PSScriptRoot

# Run the build command
npm run build

# Get the generated JavaScript file name
$generatedFile1 = Get-ChildItem -Path .\build\static\js\main.*.js | Select-Object -First 1
$generatedFile2 = Get-ChildItem -Path .\build\static\js\main.*.js.map | Select-Object -First 1
$generatedFile3 = Get-ChildItem -Path .\build\static\css\main.*.css | Select-Object -First 1
$generatedFile4 = Get-ChildItem -Path .\build\static\css\main.*.css.map | Select-Object -First 1

# Rename the generated JavaScript file
Rename-Item -Path $generatedFile1.FullName -NewName "main.js"
Rename-Item -Path $generatedFile2.FullName -NewName "main.js.map"
Rename-Item -Path $generatedFile3.FullName -NewName "main.css"
Rename-Item -Path $generatedFile4.FullName -NewName "main.css.map"
