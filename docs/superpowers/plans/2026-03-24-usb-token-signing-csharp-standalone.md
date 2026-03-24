# USB Token Signing Service (Standalone C#) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Xây dựng Windows Service (C# .NET 8) standalone tại `C:\Users\tracnn\UsbTokenSigningService\`, expose HTTP API tại `localhost:18081` để ký XML bằng USB Token.

**Architecture:** Windows Service dùng Windows CNG (`RSACng` với `CngKey.SetProperty("SmartCardPin")`) để truy cập USB Token mà không cần PIN dialog. PIN được mã hóa DPAPI LocalMachine trong config. API nhận XML base64, trả về XML đã ký base64 — cùng contract với HSM API của qlbv.

**Tech Stack:** C# .NET 8 (ASP.NET Core Minimal API, Worker Service), `System.Security.Cryptography.Xml.SignedXml`, `System.Security.Cryptography.Cng`, xUnit, Moq, Microsoft.AspNetCore.Mvc.Testing.

**Spec:** `C:\Users\tracnn\qlbv\docs\superpowers\specs\2026-03-24-usb-token-signing-design.md`

---

## File Structure

```
C:\Users\tracnn\UsbTokenSigningService\
├── UsbTokenSigningService.sln
├── src\
│   └── UsbTokenSigningService\
│       ├── UsbTokenSigningService.csproj       ← net8.0-windows, WindowsServices package
│       ├── Program.cs                           ← DI + Kestrel + /health + /api/EmrSign/SignXmlBhyt
│       ├── appsettings.json                     ← port 18081, thumbprint, DPAPI PIN, ServiceToken
│       ├── Models\
│       │   ├── SignRequest.cs                   ← deserialize request JSON
│       │   └── SignResponse.cs                  ← serialize response JSON
│       └── Services\
│           ├── IPinProvider.cs                  ← interface PIN supply
│           ├── DpapiPinProvider.cs              ← DPAPI decrypt PIN từ config
│           ├── IXmlSigningService.cs            ← interface signing (testable)
│           └── XmlSigningService.cs             ← RSACng + SignedXml RSA-SHA256
└── tests\
    └── UsbTokenSigningService.Tests\
        ├── UsbTokenSigningService.Tests.csproj  ← references main project + Moq + Mvc.Testing
        ├── PinProviderTests.cs                  ← 4 tests: PLAIN, DPAPI roundtrip, invalid prefix, empty config
        ├── XmlSigningServiceTests.cs            ← 9 tests: signature structure, algo, verify, expired cert
        └── SignEndpointTests.cs                 ← 5 tests: 200+success, 401 no token, 401 wrong, 400 bad b64, /health
```

---

## Task 1: Tạo Solution và Project Skeleton

**Files:**
- Create: `C:\Users\tracnn\UsbTokenSigningService\UsbTokenSigningService.sln`
- Create: `C:\Users\tracnn\UsbTokenSigningService\src\UsbTokenSigningService\UsbTokenSigningService.csproj`
- Create: `C:\Users\tracnn\UsbTokenSigningService\tests\UsbTokenSigningService.Tests\UsbTokenSigningService.Tests.csproj`
- Create: `C:\Users\tracnn\UsbTokenSigningService\src\UsbTokenSigningService\appsettings.json`

- [ ] **Step 1: Tạo thư mục root**

```bash
mkdir -p /c/Users/tracnn/UsbTokenSigningService
cd /c/Users/tracnn/UsbTokenSigningService
```

- [ ] **Step 2: Tạo solution và 2 projects**

```bash
cd /c/Users/tracnn/UsbTokenSigningService
dotnet new sln -n UsbTokenSigningService
dotnet new worker -n UsbTokenSigningService -o src/UsbTokenSigningService --framework net8.0
dotnet new xunit -n UsbTokenSigningService.Tests -o tests/UsbTokenSigningService.Tests --framework net8.0
dotnet sln add src/UsbTokenSigningService/UsbTokenSigningService.csproj
dotnet sln add tests/UsbTokenSigningService.Tests/UsbTokenSigningService.Tests.csproj
```

- [ ] **Step 3: Thay toàn bộ nội dung `src/UsbTokenSigningService/UsbTokenSigningService.csproj`**

```xml
<Project Sdk="Microsoft.NET.Sdk.Web">
  <PropertyGroup>
    <TargetFramework>net8.0-windows</TargetFramework>
    <Nullable>enable</Nullable>
    <ImplicitUsings>enable</ImplicitUsings>
    <RuntimeIdentifier>win-x64</RuntimeIdentifier>
    <SelfContained>false</SelfContained>
  </PropertyGroup>
  <ItemGroup>
    <PackageReference Include="Microsoft.Extensions.Hosting.WindowsServices" Version="8.*" />
  </ItemGroup>
</Project>
```

- [ ] **Step 4: Thêm packages và reference vào test project**

```bash
cd /c/Users/tracnn/UsbTokenSigningService/tests/UsbTokenSigningService.Tests
dotnet add reference ../../src/UsbTokenSigningService/UsbTokenSigningService.csproj
dotnet add package Microsoft.AspNetCore.Mvc.Testing --version 8.*
dotnet add package Moq --version 4.*
```

- [ ] **Step 5: Tạo `src/UsbTokenSigningService/appsettings.json`**

```json
{
  "Kestrel": {
    "Endpoints": {
      "Http": { "Url": "http://127.0.0.1:18081" }
    }
  },
  "SigningService": {
    "CertificateThumbprint": "",
    "TokenPin": "",
    "XmlSignatureTag": "CHUKYDONVI",
    "ServiceToken": ""
  },
  "Logging": {
    "LogLevel": {
      "Default": "Information",
      "Microsoft.AspNetCore": "Warning"
    }
  }
}
```

- [ ] **Step 6: Thêm InternalsVisibleTo để test project có thể dùng internal constructor của XmlSigningService**

Tạo file `src/UsbTokenSigningService/AssemblyInfo.cs`:

```csharp
using System.Runtime.CompilerServices;

[assembly: InternalsVisibleTo("UsbTokenSigningService.Tests")]
```

- [ ] **Step 7: Build để xác nhận project compile được**

```bash
cd /c/Users/tracnn/UsbTokenSigningService
dotnet build
```
Expected: `Build succeeded. 0 Error(s)`

---

## Task 2: Models (Request/Response DTOs)

**Files:**
- Create: `src/UsbTokenSigningService/Models/SignRequest.cs`
- Create: `src/UsbTokenSigningService/Models/SignResponse.cs`

- [ ] **Step 1: Tạo `src/UsbTokenSigningService/Models/SignRequest.cs`**

```csharp
// src/UsbTokenSigningService/Models/SignRequest.cs
namespace UsbTokenSigningService.Models;

public class SignRequest
{
    public ApiDataModel ApiData { get; set; } = new();
}

public class ApiDataModel
{
    public string XmlBase64 { get; set; } = string.Empty;
    public string TagStoreSignatureValue { get; set; } = "CHUKYDONVI";
    public object? ConfigData { get; set; }
}
```

- [ ] **Step 2: Tạo `src/UsbTokenSigningService/Models/SignResponse.cs`**

```csharp
// src/UsbTokenSigningService/Models/SignResponse.cs
namespace UsbTokenSigningService.Models;

public class SignResponse
{
    public bool Success { get; set; }
    public string? Data { get; set; }
    public ParamModel Param { get; set; } = new();

    public static SignResponse Ok(string base64SignedXml) => new()
    {
        Success = true,
        Data = base64SignedXml,
        Param = new ParamModel()
    };

    public static SignResponse Fail(string message) => new()
    {
        Success = false,
        Data = null,
        Param = new ParamModel { Messages = [message] }
    };
}

public class ParamModel
{
    public List<string> Messages { get; set; } = [];
}
```

- [ ] **Step 3: Build để xác nhận**

```bash
cd /c/Users/tracnn/UsbTokenSigningService
dotnet build
```
Expected: `Build succeeded. 0 Error(s)`

---

## Task 3: IPinProvider Interface + DpapiPinProvider + Tests (TDD)

**Files:**
- Create: `src/UsbTokenSigningService/Services/IPinProvider.cs`
- Create: `src/UsbTokenSigningService/Services/DpapiPinProvider.cs`
- Create: `tests/UsbTokenSigningService.Tests/PinProviderTests.cs`

- [ ] **Step 1: Tạo `src/UsbTokenSigningService/Services/IPinProvider.cs`**

```csharp
// src/UsbTokenSigningService/Services/IPinProvider.cs
namespace UsbTokenSigningService.Services;

public interface IPinProvider
{
    byte[] GetPinBytes();
}
```

- [ ] **Step 2: Viết failing tests trước (TDD)**

Tạo `tests/UsbTokenSigningService.Tests/PinProviderTests.cs`:

```csharp
using System.Security.Cryptography;
using System.Text;
using Microsoft.Extensions.Configuration;
using UsbTokenSigningService.Services;
using Xunit;

namespace UsbTokenSigningService.Tests;

public class PinProviderTests
{
    [Fact]
    public void PlainPrefix_ReturnsUnicodeBytes()
    {
        var config = BuildConfig("PLAIN:mypin");
        var provider = new DpapiPinProvider(config);
        var expected = Encoding.Unicode.GetBytes("mypin");
        Assert.Equal(expected, provider.GetPinBytes());
    }

    [Fact]
    public void DpapiPrefix_RoundTrips_OnSameMachine()
    {
        if (!OperatingSystem.IsWindows()) return;

        var original = Encoding.Unicode.GetBytes("testpin123");
        var encrypted = ProtectedData.Protect(original, null, DataProtectionScope.LocalMachine);
        var b64 = "DPAPI:" + Convert.ToBase64String(encrypted);

        var config = BuildConfig(b64);
        var provider = new DpapiPinProvider(config);
        Assert.Equal(original, provider.GetPinBytes());
    }

    [Fact]
    public void InvalidPrefix_Throws()
    {
        var config = BuildConfig("INVALID:xyz");
        var provider = new DpapiPinProvider(config);
        Assert.Throws<InvalidOperationException>(() => provider.GetPinBytes());
    }

    [Fact]
    public void EmptyConfig_ThrowsOnGetPinBytes_NotOnConstruction()
    {
        var config = new ConfigurationBuilder().Build();
        var provider = new DpapiPinProvider(config); // không throw ở đây

        Assert.Throws<InvalidOperationException>(() => provider.GetPinBytes()); // throw ở đây
    }

    private static IConfiguration BuildConfig(string tokenPin) =>
        new ConfigurationBuilder()
            .AddInMemoryCollection(new Dictionary<string, string?>
            {
                ["SigningService:TokenPin"] = tokenPin
            })
            .Build();
}
```

- [ ] **Step 3: Chạy tests - phải FAIL (compile error)**

```bash
cd /c/Users/tracnn/UsbTokenSigningService
dotnet test tests/UsbTokenSigningService.Tests/UsbTokenSigningService.Tests.csproj --filter "PinProviderTests"
```
Expected: FAIL — `DpapiPinProvider` chưa tồn tại.

- [ ] **Step 4: Implement `src/UsbTokenSigningService/Services/DpapiPinProvider.cs`**

```csharp
// src/UsbTokenSigningService/Services/DpapiPinProvider.cs
using System.Security.Cryptography;
using System.Text;

namespace UsbTokenSigningService.Services;

/// <summary>
/// Đọc PIN từ config và giải mã DPAPI LocalMachine scope.
/// Config format:
///   "DPAPI:&lt;base64&gt;" → giải mã DPAPI LocalMachine
///   "PLAIN:&lt;text&gt;"  → dùng thẳng (chỉ dùng khi development)
/// </summary>
public class DpapiPinProvider : IPinProvider
{
    private readonly string _rawTokenPin;

    public DpapiPinProvider(IConfiguration config)
    {
        _rawTokenPin = config["SigningService:TokenPin"] ?? "";
    }

    public byte[] GetPinBytes()
    {
        if (string.IsNullOrEmpty(_rawTokenPin))
            throw new InvalidOperationException(
                "SigningService:TokenPin chưa được cấu hình. " +
                "Chạy scripts/encrypt-token-pin.ps1 để lấy giá trị.");

        return ParsePin(_rawTokenPin);
    }

    private static byte[] ParsePin(string tokenPin)
    {
        if (tokenPin.StartsWith("DPAPI:"))
        {
            var encrypted = Convert.FromBase64String(tokenPin["DPAPI:".Length..]);
            return ProtectedData.Unprotect(
                encrypted,
                optionalEntropy: null,
                scope: DataProtectionScope.LocalMachine);
        }

        if (tokenPin.StartsWith("PLAIN:"))
        {
            return Encoding.Unicode.GetBytes(tokenPin["PLAIN:".Length..]);
        }

        throw new InvalidOperationException(
            "TokenPin format không hợp lệ. Phải là 'DPAPI:<base64>' hoặc 'PLAIN:<text>'");
    }
}
```

- [ ] **Step 5: Chạy tests - phải PASS**

```bash
cd /c/Users/tracnn/UsbTokenSigningService
dotnet test tests/UsbTokenSigningService.Tests/UsbTokenSigningService.Tests.csproj --filter "PinProviderTests"
```
Expected: 4 tests PASS (DPAPI test skip nếu non-Windows)

---

## Task 4: IXmlSigningService Interface + XmlSigningService (TDD)

**Files:**
- Create: `src/UsbTokenSigningService/Services/IXmlSigningService.cs`
- Create: `tests/UsbTokenSigningService.Tests/XmlSigningServiceTests.cs` (tests trước)
- Create: `src/UsbTokenSigningService/Services/XmlSigningService.cs` (implementation sau)

- [ ] **Step 1: Tạo `src/UsbTokenSigningService/Services/IXmlSigningService.cs`**

```csharp
// src/UsbTokenSigningService/Services/IXmlSigningService.cs
namespace UsbTokenSigningService.Services;

public interface IXmlSigningService
{
    /// <summary>
    /// Ký XML và nhúng chữ ký XMLDSig vào tag chỉ định.
    /// </summary>
    byte[] SignXml(byte[] xmlBytes, string signatureTag);
}
```

- [ ] **Step 2: Viết failing tests trước (TDD)**

Tạo `tests/UsbTokenSigningService.Tests/XmlSigningServiceTests.cs`:

```csharp
using System.Security.Cryptography;
using System.Security.Cryptography.X509Certificates;
using System.Security.Cryptography.Xml;
using System.Xml;
using UsbTokenSigningService.Services;
using Xunit;

namespace UsbTokenSigningService.Tests;

/// <summary>
/// Unit tests dùng software RSA key (không cần USB Token thật).
/// </summary>
public class XmlSigningServiceTests : IDisposable
{
    private readonly RSA _testRsa;
    private readonly X509Certificate2 _testCert;
    private readonly XmlSigningService _service;

    public XmlSigningServiceTests()
    {
        _testRsa = RSA.Create(2048);
        var req = new CertificateRequest("cn=Test", _testRsa,
            HashAlgorithmName.SHA256, RSASignaturePadding.Pkcs1);
        _testCert = req.CreateSelfSigned(
            DateTimeOffset.Now.AddDays(-1), DateTimeOffset.Now.AddYears(1));
        _service = new XmlSigningService(_testCert);
    }

    [Fact]
    public void SignXml_ProducesValidXmlDocument()
    {
        var xml = "<Root><Data>hello</Data><CHUKYDONVI/></Root>"u8.ToArray();
        var signed = _service.SignXml(xml, "CHUKYDONVI");
        var doc = new XmlDocument();
        doc.LoadXml(System.Text.Encoding.UTF8.GetString(signed));
    }

    [Fact]
    public void SignXml_EmbeddsSignatureInsideTargetTag()
    {
        var xml = "<Root><Data>test</Data><CHUKYDONVI/></Root>"u8.ToArray();
        var signed = _service.SignXml(xml, "CHUKYDONVI");

        var doc = new XmlDocument();
        doc.LoadXml(System.Text.Encoding.UTF8.GetString(signed));
        var ns = new XmlNamespaceManager(doc.NameTable);
        ns.AddNamespace("ds", "http://www.w3.org/2000/09/xmldsig#");

        var sigNode = doc.SelectSingleNode("//CHUKYDONVI/ds:Signature", ns);
        Assert.NotNull(sigNode);
    }

    [Fact]
    public void SignXml_UsesRsaSha256Algorithm()
    {
        var xml = "<Root><CHUKYDONVI/></Root>"u8.ToArray();
        var signed = _service.SignXml(xml, "CHUKYDONVI");

        var doc = new XmlDocument();
        doc.LoadXml(System.Text.Encoding.UTF8.GetString(signed));
        var ns = new XmlNamespaceManager(doc.NameTable);
        ns.AddNamespace("ds", "http://www.w3.org/2000/09/xmldsig#");

        var sigMethod = doc.SelectSingleNode("//ds:SignatureMethod/@Algorithm", ns)?.Value;
        Assert.Equal("http://www.w3.org/2001/04/xmldsig-more#rsa-sha256", sigMethod);
    }

    [Fact]
    public void SignXml_UsesSha256Digest()
    {
        var xml = "<Root><CHUKYDONVI/></Root>"u8.ToArray();
        var signed = _service.SignXml(xml, "CHUKYDONVI");

        var doc = new XmlDocument();
        doc.LoadXml(System.Text.Encoding.UTF8.GetString(signed));
        var ns = new XmlNamespaceManager(doc.NameTable);
        ns.AddNamespace("ds", "http://www.w3.org/2000/09/xmldsig#");

        var digestMethod = doc.SelectSingleNode("//ds:DigestMethod/@Algorithm", ns)?.Value;
        Assert.Equal("http://www.w3.org/2001/04/xmlenc#sha256", digestMethod);
    }

    [Fact]
    public void SignXml_IncludesX509CertInKeyInfo()
    {
        var xml = "<Root><CHUKYDONVI/></Root>"u8.ToArray();
        var signed = _service.SignXml(xml, "CHUKYDONVI");

        var doc = new XmlDocument();
        doc.LoadXml(System.Text.Encoding.UTF8.GetString(signed));
        var ns = new XmlNamespaceManager(doc.NameTable);
        ns.AddNamespace("ds", "http://www.w3.org/2000/09/xmldsig#");

        var x509cert = doc.SelectSingleNode("//ds:X509Certificate", ns)?.InnerText;
        Assert.NotNull(x509cert);
        Assert.NotEmpty(x509cert);
    }

    [Fact]
    public void SignXml_SignatureIsVerifiableWithPublicKey()
    {
        var xml = "<Root><Data>important</Data><CHUKYDONVI/></Root>"u8.ToArray();
        var signed = _service.SignXml(xml, "CHUKYDONVI");

        var doc = new XmlDocument { PreserveWhitespace = true };
        doc.LoadXml(System.Text.Encoding.UTF8.GetString(signed));

        var signedXml = new SignedXml(doc);
        var ns = new XmlNamespaceManager(doc.NameTable);
        ns.AddNamespace("ds", "http://www.w3.org/2000/09/xmldsig#");
        var sigElement = (XmlElement)doc.SelectSingleNode("//ds:Signature", ns)!;
        signedXml.LoadXml(sigElement);

        Assert.True(signedXml.CheckSignature(_testCert.GetRSAPublicKey()!));
    }

    [Fact]
    public void SignXml_SignatureIsVerifiableWithEmbeddedKeyInfo()
    {
        var xml = "<Root><Data>important</Data><CHUKYDONVI/></Root>"u8.ToArray();
        var signed = _service.SignXml(xml, "CHUKYDONVI");

        var doc = new XmlDocument { PreserveWhitespace = true };
        doc.LoadXml(System.Text.Encoding.UTF8.GetString(signed));

        var signedXml = new SignedXml(doc);
        var ns = new XmlNamespaceManager(doc.NameTable);
        ns.AddNamespace("ds", "http://www.w3.org/2000/09/xmldsig#");
        var sigElement = (XmlElement)doc.SelectSingleNode("//ds:Signature", ns)!;
        signedXml.LoadXml(sigElement);

        Assert.True(signedXml.CheckSignature());
    }

    [Fact]
    public void SignXml_CreatesTagIfMissing()
    {
        var xml = "<Root><Data>test</Data></Root>"u8.ToArray();
        var signed = _service.SignXml(xml, "CHUKYDONVI");

        var doc = new XmlDocument();
        doc.LoadXml(System.Text.Encoding.UTF8.GetString(signed));
        var ns = new XmlNamespaceManager(doc.NameTable);
        ns.AddNamespace("ds", "http://www.w3.org/2000/09/xmldsig#");

        var sigNode = doc.SelectSingleNode("//CHUKYDONVI/ds:Signature", ns);
        Assert.NotNull(sigNode);
    }

    [Fact]
    public void SignXml_ExpiredCert_ThrowsBeforeSigning()
    {
        using var rsa = RSA.Create(2048);
        var req = new CertificateRequest("cn=Expired", rsa,
            HashAlgorithmName.SHA256, RSASignaturePadding.Pkcs1);
        using var expiredCert = req.CreateSelfSigned(
            DateTimeOffset.Now.AddDays(-10), DateTimeOffset.Now.AddDays(-1));

        var serviceWithExpiredCert = new XmlSigningService(expiredCert);
        var xml = "<Root><CHUKYDONVI/></Root>"u8.ToArray();

        var ex = Assert.Throws<InvalidOperationException>(
            () => serviceWithExpiredCert.SignXml(xml, "CHUKYDONVI"));
        Assert.Contains("hết hạn", ex.Message, StringComparison.OrdinalIgnoreCase);
    }

    public void Dispose()
    {
        _testRsa.Dispose();
        _testCert.Dispose();
    }
}
```

- [ ] **Step 3: Chạy tests - phải FAIL (compile error)**

```bash
cd /c/Users/tracnn/UsbTokenSigningService
dotnet test tests/UsbTokenSigningService.Tests/UsbTokenSigningService.Tests.csproj --filter "XmlSigningServiceTests"
```
Expected: FAIL — `XmlSigningService` chưa tồn tại.

- [ ] **Step 4: Implement `src/UsbTokenSigningService/Services/XmlSigningService.cs`**

```csharp
// src/UsbTokenSigningService/Services/XmlSigningService.cs
using System.Security.Cryptography;
using System.Security.Cryptography.X509Certificates;
using System.Security.Cryptography.Xml;
using System.Text;
using System.Xml;

namespace UsbTokenSigningService.Services;

public class XmlSigningService : IXmlSigningService
{
    private readonly X509Certificate2 _certificate;
    private readonly RSA _signingKey;

    /// <summary>
    /// Constructor production: load cert từ Windows CertStore, inject PIN vào CNG key.
    /// </summary>
    public XmlSigningService(IConfiguration config, IPinProvider pinProvider,
        ILogger<XmlSigningService> logger)
    {
        var thumbprint = config["SigningService:CertificateThumbprint"]
            ?? throw new InvalidOperationException("CertificateThumbprint not configured");

        var pinBytes = pinProvider.GetPinBytes();
        (_certificate, _signingKey) = LoadCertAndKey(thumbprint, pinBytes, logger);
    }

    /// <summary>
    /// Constructor cho testing: nhận cert trực tiếp với software RSA key.
    /// </summary>
    internal XmlSigningService(X509Certificate2 testCertificate)
    {
        _certificate = testCertificate;
        _signingKey = testCertificate.GetRSAPrivateKey()
            ?? throw new ArgumentException("Certificate không có private key");
    }

    public byte[] SignXml(byte[] xmlBytes, string signatureTag)
    {
        if (_certificate.NotAfter < DateTime.UtcNow)
            throw new InvalidOperationException(
                $"Certificate đã hết hạn vào {_certificate.NotAfter:dd/MM/yyyy}. Gia hạn token trước khi ký.");

        var doc = new XmlDocument { PreserveWhitespace = true };
        doc.LoadXml(Encoding.UTF8.GetString(xmlBytes));

        var targetTag = doc.SelectSingleNode($"//{signatureTag}") as XmlElement;
        if (targetTag == null)
        {
            targetTag = doc.CreateElement(signatureTag);
            doc.DocumentElement!.AppendChild(targetTag);
        }

        var signedXml = new SignedXml(doc) { SigningKey = _signingKey };
        signedXml.SignedInfo.CanonicalizationMethod =
            "http://www.w3.org/TR/2001/REC-xml-c14n-20010315";
        signedXml.SignedInfo.SignatureMethod =
            "http://www.w3.org/2001/04/xmldsig-more#rsa-sha256";

        var reference = new Reference { Uri = "" };
        reference.AddTransform(new XmlDsigEnvelopedSignatureTransform());
        reference.DigestMethod = "http://www.w3.org/2001/04/xmlenc#sha256";
        signedXml.AddReference(reference);

        var keyInfo = new KeyInfo();
        keyInfo.AddClause(new KeyInfoX509Data(_certificate));
        signedXml.KeyInfo = keyInfo;

        signedXml.ComputeSignature();

        var signatureElement = signedXml.GetXml();
        targetTag.AppendChild(doc.ImportNode(signatureElement, true));

        using var ms = new MemoryStream();
        doc.Save(ms);
        return ms.ToArray();
    }

    private static (X509Certificate2, RSA) LoadCertAndKey(
        string thumbprint, byte[] pinBytes, ILogger logger)
    {
        var normalized = thumbprint.Replace(":", "").Replace(" ", "").ToUpperInvariant();

        foreach (var location in new[] { StoreLocation.LocalMachine, StoreLocation.CurrentUser })
        {
            using var store = new X509Store(StoreName.My, location);
            store.Open(OpenFlags.ReadOnly | OpenFlags.OpenExistingOnly);

            var certs = store.Certificates.Find(
                X509FindType.FindByThumbprint, normalized, validOnly: false);

            if (certs.Count == 0) continue;

            var cert = certs[0];
            var rsaKey = cert.GetRSAPrivateKey()
                ?? throw new InvalidOperationException("Certificate không có RSA private key");

            if (rsaKey is RSACng rsaCng)
            {
                try
                {
                    rsaCng.Key.SetProperty(
                        new CngProperty("SmartCardPin", pinBytes, CngPropertyOptions.None));
                    logger.LogInformation("PIN injected vào USB Token CNG key thành công");
                }
                catch (Exception ex)
                {
                    logger.LogWarning("Không inject được PIN vào CNG key: {Error}. " +
                        "Windows có thể prompt PIN khi ký.", ex.Message);
                }
            }

            return (cert, rsaKey);
        }

        throw new InvalidOperationException(
            $"Không tìm thấy certificate với thumbprint '{thumbprint}' " +
            "trong Windows Certificate Store (LocalMachine\\My hoặc CurrentUser\\My)");
    }
}
```

- [ ] **Step 5: Chạy tests - phải PASS**

```bash
cd /c/Users/tracnn/UsbTokenSigningService
dotnet test tests/UsbTokenSigningService.Tests/UsbTokenSigningService.Tests.csproj --filter "XmlSigningServiceTests"
```
Expected: 9 tests PASS

---

## Task 5: HTTP Endpoint + Auth (Program.cs) — TDD

**Files:**
- Create: `tests/UsbTokenSigningService.Tests/SignEndpointTests.cs` (tests trước)
- Create: `src/UsbTokenSigningService/Program.cs` (implementation sau)

- [ ] **Step 1: Viết failing tests trước (TDD)**

Tạo `tests/UsbTokenSigningService.Tests/SignEndpointTests.cs`:

```csharp
using System.Net;
using System.Net.Http.Json;
using System.Text;
using Microsoft.AspNetCore.Mvc.Testing;
using Microsoft.Extensions.DependencyInjection;
using Moq;
using UsbTokenSigningService.Models;
using UsbTokenSigningService.Services;
using Xunit;

namespace UsbTokenSigningService.Tests;

public class SignEndpointTests : IClassFixture<WebApplicationFactory<Program>>
{
    private const string ValidToken = "test-token-abc123";
    private readonly WebApplicationFactory<Program> _factory;

    public SignEndpointTests(WebApplicationFactory<Program> factory)
    {
        var mockSigner = new Mock<IXmlSigningService>();
        mockSigner
            .Setup(s => s.SignXml(It.IsAny<byte[]>(), It.IsAny<string>()))
            .Returns(Encoding.UTF8.GetBytes(
                "<Root><CHUKYDONVI><Signature/></CHUKYDONVI></Root>"));

        _factory = factory.WithWebHostBuilder(builder =>
        {
            builder.ConfigureServices(services =>
            {
                services.RemoveAll(typeof(IXmlSigningService));
                services.RemoveAll(typeof(IPinProvider));
                services.AddSingleton(mockSigner.Object);
            });
            builder.UseSetting("SigningService:ServiceToken", ValidToken);
            builder.UseSetting("SigningService:CertificateThumbprint", "TEST");
            builder.UseSetting("SigningService:TokenPin", "PLAIN:1234");
        });
    }

    [Fact]
    public async Task Post_ValidToken_Returns200AndSuccess()
    {
        var client = _factory.CreateClient();
        client.DefaultRequestHeaders.Add("X-Service-Token", ValidToken);

        var request = new SignRequest
        {
            ApiData = new ApiDataModel
            {
                XmlBase64 = Convert.ToBase64String(
                    Encoding.UTF8.GetBytes("<Root><CHUKYDONVI/></Root>")),
                TagStoreSignatureValue = "CHUKYDONVI"
            }
        };

        var response = await client.PostAsJsonAsync("/api/EmrSign/SignXmlBhyt", request);

        Assert.Equal(HttpStatusCode.OK, response.StatusCode);
        var result = await response.Content.ReadFromJsonAsync<SignResponse>();
        Assert.NotNull(result);
        Assert.True(result.Success);
        Assert.NotNull(result.Data);
    }

    [Fact]
    public async Task Post_WithoutToken_Returns401()
    {
        var client = _factory.CreateClient();
        var response = await client.PostAsJsonAsync(
            "/api/EmrSign/SignXmlBhyt",
            new SignRequest { ApiData = new ApiDataModel { XmlBase64 = "dGVzdA==" } });

        Assert.Equal(HttpStatusCode.Unauthorized, response.StatusCode);
    }

    [Fact]
    public async Task Post_WrongToken_Returns401()
    {
        var client = _factory.CreateClient();
        client.DefaultRequestHeaders.Add("X-Service-Token", "wrong-token");
        var response = await client.PostAsJsonAsync(
            "/api/EmrSign/SignXmlBhyt",
            new SignRequest { ApiData = new ApiDataModel { XmlBase64 = "dGVzdA==" } });

        Assert.Equal(HttpStatusCode.Unauthorized, response.StatusCode);
    }

    [Fact]
    public async Task Post_InvalidBase64_Returns400WithSuccessFalse()
    {
        var client = _factory.CreateClient();
        client.DefaultRequestHeaders.Add("X-Service-Token", ValidToken);
        var response = await client.PostAsJsonAsync(
            "/api/EmrSign/SignXmlBhyt",
            new SignRequest { ApiData = new ApiDataModel { XmlBase64 = "INVALID!!!" } });

        Assert.Equal(HttpStatusCode.BadRequest, response.StatusCode);
        var result = await response.Content.ReadFromJsonAsync<SignResponse>();
        Assert.NotNull(result);
        Assert.False(result.Success);
    }

    [Fact]
    public async Task Get_Health_Returns200()
    {
        var client = _factory.CreateClient();
        var response = await client.GetAsync("/health");
        Assert.Equal(HttpStatusCode.OK, response.StatusCode);
    }
}
```

- [ ] **Step 2: Chạy tests - phải FAIL (Program không tồn tại)**

```bash
cd /c/Users/tracnn/UsbTokenSigningService
dotnet test tests/UsbTokenSigningService.Tests/UsbTokenSigningService.Tests.csproj --filter "SignEndpointTests"
```
Expected: FAIL — `Program` chưa tồn tại.

- [ ] **Step 3: Implement `src/UsbTokenSigningService/Program.cs`**

Xóa file `Program.cs` được generate tự động (nếu có), tạo lại với nội dung:

```csharp
// src/UsbTokenSigningService/Program.cs
using System.Text.Json;
using UsbTokenSigningService.Models;
using UsbTokenSigningService.Services;

var builder = WebApplication.CreateBuilder(args);

builder.Host.UseWindowsService(options =>
{
    options.ServiceName = "UsbTokenSigningService";
});

builder.Services.AddSingleton<IPinProvider, DpapiPinProvider>();
builder.Services.AddSingleton<IXmlSigningService, XmlSigningService>();

var app = builder.Build();

app.MapGet("/health", () => Results.Ok(new { status = "healthy", time = DateTime.UtcNow }));

app.MapPost("/api/EmrSign/SignXmlBhyt", async (HttpContext ctx,
    IXmlSigningService signingService, IConfiguration config) =>
{
    var expectedToken = config["SigningService:ServiceToken"] ?? "";
    var providedToken = ctx.Request.Headers["X-Service-Token"].FirstOrDefault() ?? "";

    if (string.IsNullOrEmpty(expectedToken) || providedToken != expectedToken)
    {
        ctx.Response.StatusCode = 401;
        await ctx.Response.WriteAsJsonAsync(
            SignResponse.Fail("Unauthorized: X-Service-Token không hợp lệ"));
        return;
    }

    SignRequest? request;
    try
    {
        request = await JsonSerializer.DeserializeAsync<SignRequest>(
            ctx.Request.Body,
            new JsonSerializerOptions { PropertyNameCaseInsensitive = true });

        if (request?.ApiData == null || string.IsNullOrEmpty(request.ApiData.XmlBase64))
        {
            ctx.Response.StatusCode = 400;
            await ctx.Response.WriteAsJsonAsync(
                SignResponse.Fail("Request không hợp lệ: thiếu ApiData.XmlBase64"));
            return;
        }
    }
    catch (Exception ex)
    {
        ctx.Response.StatusCode = 400;
        await ctx.Response.WriteAsJsonAsync(SignResponse.Fail($"Lỗi parse request: {ex.Message}"));
        return;
    }

    byte[] xmlBytes;
    try
    {
        xmlBytes = Convert.FromBase64String(request.ApiData.XmlBase64);
    }
    catch
    {
        ctx.Response.StatusCode = 400;
        await ctx.Response.WriteAsJsonAsync(
            SignResponse.Fail("XmlBase64 không phải base64 hợp lệ"));
        return;
    }

    try
    {
        var signatureTag = request.ApiData.TagStoreSignatureValue;
        var signedBytes = signingService.SignXml(xmlBytes, signatureTag);
        await ctx.Response.WriteAsJsonAsync(
            SignResponse.Ok(Convert.ToBase64String(signedBytes)));
    }
    catch (Exception ex)
    {
        ctx.Response.StatusCode = 500;
        await ctx.Response.WriteAsJsonAsync(SignResponse.Fail($"Lỗi ký số: {ex.Message}"));
    }
});

app.Run();

// Partial class để WebApplicationFactory có thể access trong tests
public partial class Program { }
```

- [ ] **Step 4: Chạy SignEndpointTests - phải PASS**

```bash
cd /c/Users/tracnn/UsbTokenSigningService
dotnet test tests/UsbTokenSigningService.Tests/UsbTokenSigningService.Tests.csproj --filter "SignEndpointTests"
```
Expected: 5 tests PASS

- [ ] **Step 5: Chạy toàn bộ test suite**

```bash
cd /c/Users/tracnn/UsbTokenSigningService
dotnet test
```
Expected: 18 tests PASS (4 PinProvider + 9 XmlSigning + 5 SignEndpoint)

---

## Kiểm tra tổng thể

- [ ] `dotnet build` → `Build succeeded. 0 Error(s)`
- [ ] `dotnet test` → 18 tests PASS
- [ ] Service chạy thử: `dotnet run --project src/UsbTokenSigningService` → không crash
- [ ] Health: `curl http://127.0.0.1:18081/health` → `{"status":"healthy",...}`

## Ghi chú Deploy (thực hiện thủ công trên Windows Server)

Khi triển khai thật với USB Token:

1. Build release: `dotnet publish src/UsbTokenSigningService -c Release -r win-x64 --self-contained false -o C:\Services\UsbTokenSign`
2. Encrypt PIN: chạy script PowerShell riêng để lấy `DPAPI:<base64>`
3. Cập nhật `appsettings.json` trên server: thumbprint, DPAPI PIN, ServiceToken ngẫu nhiên
4. Cài service: `sc.exe create UsbTokenSign binPath= "..." start= auto && sc.exe start UsbTokenSign`
