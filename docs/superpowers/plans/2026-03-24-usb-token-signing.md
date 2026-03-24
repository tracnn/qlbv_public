# USB Token Signing Service Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Xây dựng Windows Service (C# .NET 8) expose HTTP API tại `localhost:18081` để ký XML bằng USB Token, và cập nhật Laravel `XMLSignService` để gọi service này như một signing mode mới bên cạnh HSM.

**Architecture:** Windows Service dùng Windows CNG (`RSACng` với `CngKey.SetProperty("SmartCardPin")`) để truy cập USB Token mà không cần PIN dialog. PIN được mã hóa DPAPI LocalMachine trong config. Laravel gọi service qua HTTP giống HSM API hiện tại, chỉ thêm config block và method mới - không đụng code cũ.

**Tech Stack:** C# .NET 8 (ASP.NET Core Minimal API, Worker Service), `System.Security.Cryptography.Xml.SignedXml`, `System.Security.Cryptography.Cng`, xUnit, Moq; PHP/Laravel (GuzzleHttp), PHPUnit.

**Spec:** `docs/superpowers/specs/2026-03-24-usb-token-signing-design.md`

---

## File Structure

### Tạo mới (C# project)
```
UsbTokenSigningService/
├── UsbTokenSigningService.csproj
├── Program.cs                              ← DI + Kestrel + Windows Service host
├── appsettings.json                        ← config (port, thumbprint, token, PIN)
├── Models/
│   ├── SignRequest.cs                      ← deserialize request JSON
│   └── SignResponse.cs                     ← serialize response JSON
├── Services/
│   ├── IXmlSigningService.cs               ← interface (testable)
│   ├── XmlSigningService.cs                ← core: CNG + SignedXml logic
│   ├── IPinProvider.cs                     ← interface PIN supply
│   └── DpapiPinProvider.cs                 ← DPAPI decrypt PIN từ config
└── UsbTokenSigningService.Tests/
    ├── UsbTokenSigningService.Tests.csproj
    ├── PinProviderTests.cs
    ├── XmlSigningServiceTests.cs           ← unit test với software RSA (không cần token)
    └── SignEndpointTests.cs                ← test HTTP endpoint
```

### Sửa đổi (Laravel)
```
config/organization.php                     ← thêm 'usb_token_sign' block
app/Services/XMLSignService.php             ← thêm signWithUsbToken(), sửa isEnabled(), signXml()
.env                                        ← thêm USB_TOKEN_* vars (không commit)
.env.example                                ← thêm vars với giá trị rỗng
```

---

## Task 1: Thiết lập .gitignore và tạo C# project skeleton

**Files:**
- Modify: `.gitignore`
- Create: `UsbTokenSigningService/UsbTokenSigningService.csproj`
- Create: `UsbTokenSigningService/appsettings.json`
- Create: `UsbTokenSigningService.Tests/UsbTokenSigningService.Tests.csproj`

- [ ] **Step 1: Thêm .gitignore entries cho C# trước khi tạo project**

Thêm vào `.gitignore` (tạo nếu chưa có):
```
# C# build artifacts
UsbTokenSigningService/bin/
UsbTokenSigningService/obj/
UsbTokenSigningService.Tests/bin/
UsbTokenSigningService.Tests/obj/
*.user
.vs/
# appsettings.Development.json chứa plain PIN - không commit
UsbTokenSigningService/appsettings.Development.json
```

```bash
cd /c/Users/tracnn/qlbv
git add .gitignore
git commit -m "chore: gitignore for C# build artifacts"
```

- [ ] **Step 2: Tạo project**

```bash
cd /c/Users/tracnn/qlbv
dotnet new worker -n UsbTokenSigningService --framework net8.0
dotnet new xunit -n UsbTokenSigningService.Tests --framework net8.0
```

- [ ] **Step 3: Cập nhật UsbTokenSigningService.csproj**

Thay toàn bộ nội dung `UsbTokenSigningService/UsbTokenSigningService.csproj`:
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

- [ ] **Step 4: Thêm packages vào test project**

```bash
cd /c/Users/tracnn/qlbv/UsbTokenSigningService.Tests
dotnet add reference ../UsbTokenSigningService/UsbTokenSigningService.csproj
dotnet add package Microsoft.AspNetCore.Mvc.Testing --version 8.*
dotnet add package Moq --version 4.*
```

- [ ] **Step 5: Tạo appsettings.json**

`UsbTokenSigningService/appsettings.json`:
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

`UsbTokenSigningService/appsettings.Development.json` (FILE NÀY ĐÃ ĐƯỢC GITIGNORE - không commit):
```json
{
  "Kestrel": {
    "Endpoints": {
      "Http": { "Url": "http://127.0.0.1:18082" }
    }
  },
  "SigningService": {
    "CertificateThumbprint": "TEST_THUMBPRINT",
    "TokenPin": "PLAIN:12345678",
    "XmlSignatureTag": "CHUKYDONVI",
    "ServiceToken": "dev-secret-token"
  }
}
```

- [ ] **Step 6: Build để xác nhận**

```bash
cd /c/Users/tracnn/qlbv/UsbTokenSigningService
dotnet build
```
Expected: `Build succeeded. 0 Error(s)`

- [ ] **Step 7: Commit**

```bash
cd /c/Users/tracnn/qlbv
git add UsbTokenSigningService/ UsbTokenSigningService.Tests/
git commit -m "feat: scaffold UsbTokenSigningService C# project"
```

---

## Task 2: Models (Request/Response DTOs)

**Files:**
- Create: `UsbTokenSigningService/Models/SignRequest.cs`
- Create: `UsbTokenSigningService/Models/SignResponse.cs`

- [ ] **Step 1: Tạo SignRequest.cs**

```csharp
// UsbTokenSigningService/Models/SignRequest.cs
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

- [ ] **Step 2: Tạo SignResponse.cs**

```csharp
// UsbTokenSigningService/Models/SignResponse.cs
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

- [ ] **Step 3: Commit**

```bash
git add UsbTokenSigningService/Models/
git commit -m "feat: add SignRequest and SignResponse DTOs"
```

---

## Task 3: IPinProvider interface + DpapiPinProvider

**Files:**
- Create: `UsbTokenSigningService/Services/IPinProvider.cs`
- Create: `UsbTokenSigningService/Services/DpapiPinProvider.cs`
- Create: `UsbTokenSigningService.Tests/PinProviderTests.cs`

- [ ] **Step 1: Tạo IPinProvider.cs**

```csharp
// UsbTokenSigningService/Services/IPinProvider.cs
namespace UsbTokenSigningService.Services;

public interface IPinProvider
{
    byte[] GetPinBytes();
}
```

- [ ] **Step 2: Tạo DpapiPinProvider.cs**

```csharp
// UsbTokenSigningService/Services/DpapiPinProvider.cs
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
        // Đọc config nhưng CHƯA parse - defer đến GetPinBytes() lần đầu
        // Để tránh crash khi service start với config rỗng
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
            // Chỉ dùng trong development
            return Encoding.Unicode.GetBytes(tokenPin["PLAIN:".Length..]);
        }

        throw new InvalidOperationException(
            "TokenPin format không hợp lệ. Phải là 'DPAPI:<base64>' hoặc 'PLAIN:<text>'");
    }
}
```

- [ ] **Step 3: Viết unit test**

`UsbTokenSigningService.Tests/PinProviderTests.cs`:
```csharp
using System.Runtime.Versioning;
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
    [SupportedOSPlatform("windows")]
    [System.Runtime.InteropServices.OSSkipConditionAttribute(
        typeof(System.Runtime.InteropServices.OSPlatform),
        nameof(System.Runtime.InteropServices.OSPlatform.Linux))]
    public void DpapiPrefix_RoundTrips_OnSameMachine()
    {
        // Skip on non-Windows: DPAPI không hỗ trợ Linux/Mac
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
        // Service phải khởi động được dù config rỗng
        // Chỉ throw khi GetPinBytes() được gọi
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

- [ ] **Step 4: Chạy tests**

```bash
cd /c/Users/tracnn/qlbv/UsbTokenSigningService.Tests
dotnet test --filter "PinProviderTests"
```
Expected: 4 tests PASS (DPAPI test sẽ skip nếu chạy trên Linux CI)

- [ ] **Step 5: Commit**

```bash
cd /c/Users/tracnn/qlbv
git add UsbTokenSigningService/Services/IPinProvider.cs \
        UsbTokenSigningService/Services/DpapiPinProvider.cs \
        UsbTokenSigningService.Tests/PinProviderTests.cs
git commit -m "feat: add DPAPI pin provider with unit tests"
```

---

## Task 4: IXmlSigningService interface + TDD implementation

**Files:**
- Create: `UsbTokenSigningService/Services/IXmlSigningService.cs`
- Create: `UsbTokenSigningService.Tests/XmlSigningServiceTests.cs` (tests trước)
- Create: `UsbTokenSigningService/Services/XmlSigningService.cs` (implementation sau)

- [ ] **Step 1: Tạo IXmlSigningService.cs**

```csharp
// UsbTokenSigningService/Services/IXmlSigningService.cs
namespace UsbTokenSigningService.Services;

public interface IXmlSigningService
{
    /// <summary>
    /// Ký XML và nhúng chữ ký XMLDSig vào tag chỉ định.
    /// </summary>
    byte[] SignXml(byte[] xmlBytes, string signatureTag);
}
```

- [ ] **Step 2: Viết failing tests**

`UsbTokenSigningService.Tests/XmlSigningServiceTests.cs`:
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

        // Verify bằng public key (explicit check)
        Assert.True(signedXml.CheckSignature(_testCert.GetRSAPublicKey()!));
    }

    [Fact]
    public void SignXml_SignatureIsVerifiableWithEmbeddedKeyInfo()
    {
        // Verify bằng KeyInfo nhúng trong chữ ký (như verifier bên ngoài sẽ làm)
        var xml = "<Root><Data>important</Data><CHUKYDONVI/></Root>"u8.ToArray();
        var signed = _service.SignXml(xml, "CHUKYDONVI");

        var doc = new XmlDocument { PreserveWhitespace = true };
        doc.LoadXml(System.Text.Encoding.UTF8.GetString(signed));

        var signedXml = new SignedXml(doc);
        var ns = new XmlNamespaceManager(doc.NameTable);
        ns.AddNamespace("ds", "http://www.w3.org/2000/09/xmldsig#");
        var sigElement = (XmlElement)doc.SelectSingleNode("//ds:Signature", ns)!;
        signedXml.LoadXml(sigElement);

        // CheckSignature() không có args: dùng KeyInfo trong chữ ký
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

- [ ] **Step 3: Chạy tests - phải FAIL**

```bash
cd /c/Users/tracnn/qlbv/UsbTokenSigningService.Tests
dotnet test --filter "XmlSigningServiceTests"
```
Expected: Compilation error vì `XmlSigningService` chưa tồn tại.

- [ ] **Step 4: Implement XmlSigningService.cs**

```csharp
// UsbTokenSigningService/Services/XmlSigningService.cs
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
    /// PIN được inject TRƯỚC KHI trả về RSA instance để tránh bị mất scope.
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
        // Kiểm tra cert chưa hết hạn
        if (_certificate.NotAfter < DateTime.UtcNow)
            throw new InvalidOperationException(
                $"Certificate đã hết hạn vào {_certificate.NotAfter:dd/MM/yyyy}. Gia hạn token trước khi ký.");

        var doc = new XmlDocument { PreserveWhitespace = true };
        doc.LoadXml(Encoding.UTF8.GetString(xmlBytes));

        // Tìm hoặc tạo tag chứa chữ ký
        var targetTag = doc.SelectSingleNode($"//{signatureTag}") as XmlElement;
        if (targetTag == null)
        {
            targetTag = doc.CreateElement(signatureTag);
            doc.DocumentElement!.AppendChild(targetTag);
        }

        // Cấu hình XMLDSig
        var signedXml = new SignedXml(doc)
        {
            SigningKey = _signingKey
        };

        signedXml.SignedInfo.CanonicalizationMethod =
            "http://www.w3.org/TR/2001/REC-xml-c14n-20010315";
        signedXml.SignedInfo.SignatureMethod =
            "http://www.w3.org/2001/04/xmldsig-more#rsa-sha256";

        var reference = new Reference { Uri = "" };
        reference.AddTransform(new XmlDsigEnvelopedSignatureTransform());
        reference.DigestMethod = "http://www.w3.org/2001/04/xmlenc#sha256";
        signedXml.AddReference(reference);

        // KeyInfo: nhúng full X509Certificate
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

    /// <summary>
    /// Load cert và RSA key từ Windows Certificate Store.
    /// PIN được set vào _signingKey TRƯỚC KHI return để tránh mất scope.
    /// </summary>
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

            // Inject PIN vào RSACng instance này - phải làm TRƯỚC KHI return
            // Vì _signingKey sẽ là CÙNG instance này, PIN sẽ còn hiệu lực khi ComputeSignature() gọi
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

            // Trả về cert và CÙNG rsaKey instance đã có PIN
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
cd /c/Users/tracnn/qlbv/UsbTokenSigningService.Tests
dotnet test --filter "XmlSigningServiceTests"
```
Expected: 8 tests PASS

- [ ] **Step 6: Commit**

```bash
cd /c/Users/tracnn/qlbv
git add UsbTokenSigningService/Services/ UsbTokenSigningService.Tests/
git commit -m "feat: implement XmlSigningService with TDD (8 tests)"
```

---

## Task 5: HTTP Endpoint + Auth (Program.cs)

**Files:**
- Create: `UsbTokenSigningService/Program.cs`
- Create: `UsbTokenSigningService.Tests/SignEndpointTests.cs`

- [ ] **Step 1: Viết tests trước**

`UsbTokenSigningService.Tests/SignEndpointTests.cs`:
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
                // PHẢI remove trước khi add mock để tránh DI conflict
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

- [ ] **Step 2: Chạy tests - phải FAIL**

```bash
dotnet test --filter "SignEndpointTests"
```
Expected: FAIL vì `Program` class chưa tồn tại.

- [ ] **Step 3: Implement Program.cs**

```csharp
// UsbTokenSigningService/Program.cs
using System.Text.Json;
using UsbTokenSigningService.Models;
using UsbTokenSigningService.Services;

var builder = WebApplication.CreateBuilder(args);

// Windows Service support
builder.Host.UseWindowsService(options =>
{
    options.ServiceName = "UsbTokenSigningService";
});

// Đăng ký services
builder.Services.AddSingleton<IPinProvider, DpapiPinProvider>();
builder.Services.AddSingleton<IXmlSigningService, XmlSigningService>();

var app = builder.Build();

// Health check
app.MapGet("/health", () => Results.Ok(new { status = "healthy", time = DateTime.UtcNow }));

// Endpoint ký số XML - cùng path với HSM API
app.MapPost("/api/EmrSign/SignXmlBhyt", async (HttpContext ctx,
    IXmlSigningService signingService, IConfiguration config) =>
{
    // Auth: kiểm tra X-Service-Token
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

- [ ] **Step 4: Chạy tests - phải PASS**

```bash
cd /c/Users/tracnn/qlbv/UsbTokenSigningService.Tests
dotnet test --filter "SignEndpointTests"
```
Expected: 5 tests PASS

- [ ] **Step 5: Chạy toàn bộ test suite**

```bash
dotnet test
```
Expected: Tất cả PASS (4 + 8 + 5 = 17 tests)

- [ ] **Step 6: Commit**

```bash
cd /c/Users/tracnn/qlbv
git add UsbTokenSigningService/Program.cs UsbTokenSigningService.Tests/SignEndpointTests.cs
git commit -m "feat: add HTTP endpoint with X-Service-Token auth (5 tests)"
```

---

## Task 6: Thay đổi Laravel - config/organization.php

**Files:**
- Modify: `config/organization.php`

- [ ] **Step 1: Thêm usb_token_sign block**

Mở `config/organization.php`, thêm block sau sau phần `'xml_sign' => [...]`:

```php
'usb_token_sign' => [
    'endpoint'                  => env('USB_TOKEN_ENDPOINT', 'http://127.0.0.1:18081/api/EmrSign/SignXmlBhyt'),
    'enabled'                   => env('USB_TOKEN_SIGN_ENABLED', false),
    'service_token'             => env('USB_TOKEN_SERVICE_TOKEN', ''),
    'tag_store_signature_value' => 'CHUKYDONVI',
    'timeout'                   => 30,
],
```

- [ ] **Step 2: Xác nhận config load được**

```bash
cd /c/Users/tracnn/qlbv
php artisan tinker --execute="dump(config('organization.usb_token_sign'));"
```
Expected: Array với `endpoint`, `enabled`, `service_token`, `tag_store_signature_value`, `timeout`.

- [ ] **Step 3: Commit**

```bash
git add config/organization.php
git commit -m "feat: add usb_token_sign config block"
```

---

## Task 7: Thay đổi Laravel - XMLSignService.php

**Files:**
- Modify: `app/Services/XMLSignService.php`
- Create: `tests/Unit/Services/XMLSignServiceUsbTokenTest.php`

- [ ] **Step 1: Viết PHPUnit tests (TDD - trước implementation)**

Tạo `tests/Unit/Services/XMLSignServiceUsbTokenTest.php`:

```php
<?php

namespace Tests\Unit\Services;

use App\Services\ACSLoginService;
use App\Services\XMLSignService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class XMLSignServiceUsbTokenTest extends TestCase
{
    private function makeService(array $usbConfig, MockHandler $mock): XMLSignService
    {
        Config::set('organization.usb_token_sign', $usbConfig);
        Config::set('organization.xml_sign', ['enabled' => false]);

        // Mock ACSLoginService để tránh HTTP calls trong constructor/usage
        $mockAcs = $this->createMock(ACSLoginService::class);
        $service = new XMLSignService($mockAcs);

        // Inject mock Guzzle client qua reflection
        $stack = HandlerStack::create($mock);
        $client = new Client(['handler' => $stack]);
        $ref = new \ReflectionProperty(XMLSignService::class, 'httpClient');
        $ref->setAccessible(true);
        $ref->setValue($service, $client);

        return $service;
    }

    /** @test */
    public function usb_token_mode_calls_local_service_and_returns_signed_xml(): void
    {
        $signedXml = '<Root><CHUKYDONVI><Signature/></CHUKYDONVI></Root>';
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'Success' => true,
                'Data'    => base64_encode($signedXml),
                'Param'   => ['Messages' => []],
            ])),
        ]);

        $service = $this->makeService([
            'enabled'                   => true,
            'endpoint'                  => 'http://127.0.0.1:18081/api/EmrSign/SignXmlBhyt',
            'service_token'             => 'test-token',
            'tag_store_signature_value' => 'CHUKYDONVI',
            'timeout'                   => 30,
        ], $mock);

        $result = $service->signXml('<Root><CHUKYDONVI/></Root>');

        $this->assertTrue($result['isSigned']);
        $this->assertEquals($signedXml, $result['data']);
    }

    /** @test */
    public function usb_token_service_failure_returns_isSigned_false_with_error(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'Success' => false,
                'Data'    => null,
                'Param'   => ['Messages' => ['PIN không chính xác']],
            ])),
        ]);

        $service = $this->makeService([
            'enabled'       => true,
            'endpoint'      => 'http://127.0.0.1:18081/api/EmrSign/SignXmlBhyt',
            'service_token' => 'test-token',
            'tag_store_signature_value' => 'CHUKYDONVI',
            'timeout'       => 30,
        ], $mock);

        $result = $service->signXml('<Root/>');

        $this->assertFalse($result['isSigned']);
        $this->assertStringContainsString('PIN', $result['error']);
    }

    /** @test */
    public function isEnabled_true_when_only_usb_token_enabled(): void
    {
        Config::set('organization.usb_token_sign', ['enabled' => true]);
        Config::set('organization.xml_sign', ['enabled' => false]);
        $mockAcs = $this->createMock(ACSLoginService::class);

        $service = new XMLSignService($mockAcs);
        $this->assertTrue($service->isEnabled());
    }

    /** @test */
    public function isEnabled_false_when_both_disabled(): void
    {
        Config::set('organization.usb_token_sign', ['enabled' => false]);
        Config::set('organization.xml_sign', ['enabled' => false]);
        $mockAcs = $this->createMock(ACSLoginService::class);

        $service = new XMLSignService($mockAcs);
        $this->assertFalse($service->isEnabled());
    }

    /** @test */
    public function request_sends_x_service_token_header(): void
    {
        $capturedHeaders = null;
        $mock = new MockHandler([
            function ($request) use (&$capturedHeaders) {
                $capturedHeaders = $request->getHeaders();
                return new Response(200, [], json_encode([
                    'Success' => true,
                    'Data'    => base64_encode('<Root/>'),
                    'Param'   => ['Messages' => []],
                ]));
            },
        ]);

        $service = $this->makeService([
            'enabled'       => true,
            'endpoint'      => 'http://127.0.0.1:18081/api/EmrSign/SignXmlBhyt',
            'service_token' => 'my-secret-token',
            'tag_store_signature_value' => 'CHUKYDONVI',
            'timeout'       => 30,
        ], $mock);

        $service->signXml('<Root/>');

        $this->assertArrayHasKey('X-Service-Token', $capturedHeaders);
        $this->assertEquals(['my-secret-token'], $capturedHeaders['X-Service-Token']);
    }

    /** @test */
    public function configData_serializes_as_json_object_not_array(): void
    {
        $capturedBody = null;
        $mock = new MockHandler([
            function ($request) use (&$capturedBody) {
                $capturedBody = json_decode($request->getBody()->getContents(), true);
                return new Response(200, [], json_encode([
                    'Success' => true,
                    'Data'    => base64_encode('<Root/>'),
                    'Param'   => ['Messages' => []],
                ]));
            },
        ]);

        $service = $this->makeService([
            'enabled'       => true,
            'endpoint'      => 'http://127.0.0.1:18081/api/EmrSign/SignXmlBhyt',
            'service_token' => 'x',
            'tag_store_signature_value' => 'CHUKYDONVI',
            'timeout'       => 30,
        ], $mock);

        $service->signXml('<Root/>');

        $this->assertNotNull($capturedBody);
        // ConfigData phải là {} (object) không phải [] (array)
        // json_decode với true: object rỗng {} → [] (PHP associative array)
        // array rỗng [] → [] (PHP indexed array)
        // Cách phân biệt: re-encode và check
        $recoded = json_encode($capturedBody['ApiData']['ConfigData']);
        $this->assertEquals('{}', $recoded, 'ConfigData phải encode là {} không phải []');
    }
}
```

- [ ] **Step 2: Chạy tests - phải FAIL**

```bash
cd /c/Users/tracnn/qlbv
php artisan test tests/Unit/Services/XMLSignServiceUsbTokenTest.php
```
Expected: FAIL vì `usb_token_sign` config check và `signWithUsbToken()` chưa có.

- [ ] **Step 3: Implement thay đổi trong XMLSignService.php**

Thay TOÀN BỘ method `signXml()` và `isEnabled()` trong `app/Services/XMLSignService.php`, đồng thời thêm `signWithUsbToken()`. File sau khi sửa xong:

```php
<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class XMLSignService
{
    private $httpClient;
    private $config;
    private $acsLoginService;

    public function __construct(ACSLoginService $acsLoginService = null)
    {
        $this->httpClient = new Client();
        $this->config = Config::get('organization.xml_sign', []);
        $this->acsLoginService = $acsLoginService ?: new ACSLoginService();
    }

    /**
     * Ký số XML.
     * Ưu tiên USB Token mode nếu usb_token_sign.enabled = true.
     * Fallback về HSM mode nếu xml_sign.enabled = true.
     */
    public function signXml($xmlContent)
    {
        $usbConfig = Config::get('organization.usb_token_sign', []);
        $hsmEnabled = !empty($this->config['enabled']);
        $usbEnabled = !empty($usbConfig['enabled']);

        // Cảnh báo nếu cả hai cùng bật (USB Token sẽ được ưu tiên)
        if ($usbEnabled && $hsmEnabled) {
            Log::warning('XMLSignService: Cả USB Token và HSM đều enabled. USB Token được ưu tiên.');
        }

        // USB Token mode
        if ($usbEnabled) {
            return $this->signWithUsbToken($xmlContent, $usbConfig);
        }

        // HSM mode (code cũ giữ nguyên)
        if (!$hsmEnabled) {
            Log::info('XML signing is disabled');
            return ['isSigned' => false, 'data' => $xmlContent];
        }

        $xmlBase64 = base64_encode($xmlContent);

        $data = [
            'ApiData' => [
                'XmlBase64' => $xmlBase64,
                'TagStoreSignatureValue' => $this->config['tag_store_signature_value'],
                'ConfigData' => [
                    'HsmType' => $this->config['hsm_type'],
                    'HsmUserCode' => $this->config['hsm_user_code'],
                    'Password' => $this->config['password'],
                    'SecretKey' => $this->config['secret_key'],
                    'IdentityNumber' => $this->config['identity_number'],
                    'HsmSerialNumber' => $this->config['hsm_serial_number']
                ]
            ]
        ];

        try {
            $tokenCode = $this->acsLoginService->getToken();

            $response = $this->httpClient->post($this->config['endpoint'], [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'TokenCode' => $tokenCode,
                    'ApplicationCode' => $this->config['application_code']
                ],
                'json' => $data
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (!$result['Success']) {
                $errorMessage = 'XML signing failed';
                if (!empty($result['Param']['Messages'])) {
                    $errorMessage .= ': ' . implode(', ', $result['Param']['Messages']);
                }
                Log::error('XML signing failed: ' . $errorMessage);
                return ['isSigned' => false, 'data' => $xmlContent, 'error' => $errorMessage];
            }

            return ['isSigned' => true, 'data' => base64_decode($result['Data'])];

        } catch (GuzzleException $e) {
            Log::error('XML Sign API Error: ' . $e->getMessage());
            return ['isSigned' => false, 'data' => $xmlContent, 'error' => $e->getMessage()];
        }
    }

    /**
     * Trả về true nếu BẤT KỲ signing mode nào đang enabled.
     */
    public function isEnabled(): bool
    {
        $hsmEnabled = !empty($this->config['enabled']);
        $usbEnabled = !empty(Config::get('organization.usb_token_sign.enabled'));
        return $hsmEnabled || $usbEnabled;
    }

    /**
     * Ký bằng USB Token qua local Windows service.
     */
    private function signWithUsbToken(string $xmlContent, array $usbConfig): array
    {
        try {
            $response = $this->httpClient->post($usbConfig['endpoint'], [
                'headers' => [
                    'Content-Type'    => 'application/json',
                    'X-Service-Token' => $usbConfig['service_token'] ?? '',
                ],
                'json' => [
                    'ApiData' => [
                        'XmlBase64'              => base64_encode($xmlContent),
                        'TagStoreSignatureValue' => $usbConfig['tag_store_signature_value'] ?? 'CHUKYDONVI',
                        'ConfigData'             => new \stdClass(), // phải là {} không phải []
                    ],
                ],
                'timeout' => $usbConfig['timeout'] ?? 30,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (!$result['Success']) {
                $error = implode(', ', $result['Param']['Messages'] ?? ['Unknown error']);
                Log::error('USB Token signing failed: ' . $error);
                return ['isSigned' => false, 'data' => $xmlContent, 'error' => $error];
            }

            return ['isSigned' => true, 'data' => base64_decode($result['Data'])];

        } catch (GuzzleException $e) {
            Log::error('USB Token Sign Service Error: ' . $e->getMessage());
            return ['isSigned' => false, 'data' => $xmlContent, 'error' => $e->getMessage()];
        }
    }
}
```

- [ ] **Step 4: Chạy PHP tests - phải PASS**

```bash
php artisan test tests/Unit/Services/XMLSignServiceUsbTokenTest.php
```
Expected: 6 tests PASS

- [ ] **Step 5: Regression check - toàn bộ test suite**

```bash
php artisan test
```
Expected: Không có test nào fail mới.

- [ ] **Step 6: Commit**

```bash
git add app/Services/XMLSignService.php tests/Unit/Services/XMLSignServiceUsbTokenTest.php
git commit -m "feat: add USB Token signing mode to XMLSignService (6 tests)"
```

---

## Task 8: Script mã hóa PIN (PowerShell)

**Files:**
- Create: `scripts/encrypt-token-pin.ps1`

- [ ] **Step 1: Tạo script**

`scripts/encrypt-token-pin.ps1`:
```powershell
<#
.SYNOPSIS
    Mã hóa PIN của USB Token bằng Windows DPAPI (LocalMachine scope)
    để dùng trong appsettings.json của UsbTokenSigningService.

.EXAMPLE
    .\encrypt-token-pin.ps1 -Pin "12345678"
    Output: DPAPI:AQAAANCMnd8...

    Paste giá trị này vào appsettings.json -> SigningService -> TokenPin
#>
param(
    [Parameter(Mandatory=$true)]
    [string]$Pin
)

Add-Type -AssemblyName System.Security

$pinBytes = [System.Text.Encoding]::Unicode.GetBytes($Pin)
$encrypted = [System.Security.Cryptography.ProtectedData]::Protect(
    $pinBytes,
    $null,
    [System.Security.Cryptography.DataProtectionScope]::LocalMachine
)
$base64 = [Convert]::ToBase64String($encrypted)
Write-Host ""
Write-Host "Paste vào appsettings.json -> SigningService -> TokenPin:"
Write-Host "DPAPI:$base64"
Write-Host ""
Write-Host "LƯU Ý: Giá trị này chỉ giải mã được trên máy Windows này."
```

- [ ] **Step 2: Commit**

```bash
git add scripts/encrypt-token-pin.ps1
git commit -m "feat: add PowerShell PIN encryption helper"
```

---

## Task 9: Build và cài đặt Windows Service (thực hiện trên server)

> **LƯU Ý:** Task này thực hiện trên Windows Server thật với USB Token đã cắm.
> Thay `C:\Users\tracnn\qlbv` bằng đường dẫn thực tế của project trên server.

- [ ] **Step 1: Build release trên Windows Server**

```powershell
cd C:\Users\tracnn\qlbv\UsbTokenSigningService
dotnet publish -c Release -r win-x64 --self-contained false -o C:\Services\UsbTokenSign
```

- [ ] **Step 2: Lấy thumbprint certificate**

```powershell
# Kiểm tra LocalMachine store trước (driver cài system-wide)
Get-ChildItem -Path Cert:\LocalMachine\My | Format-List Thumbprint, Subject, NotAfter

# Nếu không có, thử CurrentUser store
Get-ChildItem -Path Cert:\CurrentUser\My | Format-List Thumbprint, Subject, NotAfter
```
Tìm certificate của USB Token (Subject sẽ chứa tên tổ chức).

- [ ] **Step 3: Mã hóa PIN**

```powershell
C:\Users\tracnn\qlbv\scripts\encrypt-token-pin.ps1 -Pin "PIN_CUA_TOKEN"
```
Copy output `DPAPI:...`.

- [ ] **Step 4: Cập nhật appsettings.json trên server**

Sửa `C:\Services\UsbTokenSign\appsettings.json`:
```json
{
  "Kestrel": {
    "Endpoints": {
      "Http": { "Url": "http://127.0.0.1:18081" }
    }
  },
  "SigningService": {
    "CertificateThumbprint": "PASTE_THUMBPRINT_TU_BUOC_2",
    "TokenPin": "PASTE_DPAPI_VALUE_TU_BUOC_3",
    "XmlSignatureTag": "CHUKYDONVI",
    "ServiceToken": "RANDOM_SECRET_MIN_32_CHARS"
  }
}
```

- [ ] **Step 5: Cài Windows Service**

```powershell
sc.exe create UsbTokenSign `
  binPath= "C:\Services\UsbTokenSign\UsbTokenSigningService.exe" `
  DisplayName= "USB Token XML Signing Service" `
  start= auto

sc.exe start UsbTokenSign
sc.exe query UsbTokenSign
```
Expected: `STATE: 4 RUNNING`

- [ ] **Step 6: Test health và signing endpoint**

```powershell
# Health check
Invoke-RestMethod -Uri "http://127.0.0.1:18081/health"
# Expected: {"status":"healthy","time":"..."}

# Test signing (thay SECRET và tạo XML base64 thật)
$xml = '<Root><CHUKYDONVI/></Root>'
$xmlB64 = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($xml))
$secret = "RANDOM_SECRET_MIN_32_CHARS"  # phải khớp với ServiceToken trong appsettings.json

Invoke-RestMethod `
  -Uri "http://127.0.0.1:18081/api/EmrSign/SignXmlBhyt" `
  -Method POST `
  -ContentType "application/json" `
  -Headers @{ "X-Service-Token" = $secret } `
  -Body (ConvertTo-Json @{
    ApiData = @{
      XmlBase64 = $xmlB64
      TagStoreSignatureValue = "CHUKYDONVI"
      ConfigData = @{}
    }
  } -Depth 5)
# Expected: {"Success":true,"Data":"...","Param":{"Messages":[]}}
```

---

## Task 10: Cấu hình Laravel và test end-to-end

- [ ] **Step 1: Thêm vào .env**

```bash
USB_TOKEN_SIGN_ENABLED=true
USB_TOKEN_ENDPOINT=http://127.0.0.1:18081/api/EmrSign/SignXmlBhyt
USB_TOKEN_SERVICE_TOKEN=RANDOM_SECRET_MIN_32_CHARS
```

- [ ] **Step 2: Clear cache**

```bash
php artisan config:clear
```

- [ ] **Step 3: Test qua tinker**

```bash
php artisan tinker
```
```php
$service = app(\App\Services\XMLSignService::class);
echo $service->isEnabled(); // true
$result = $service->signXml('<Root><CHUKYDONVI/></Root>');
echo $result['isSigned']; // true
echo strlen($result['data']); // > 100
```

- [ ] **Step 4: Test end-to-end với XML QD130/3176 thật**

Vào UI → chọn 1 bệnh nhân → Export XML → kiểm tra:
- DB: `is_signed = 1`, `signed_error = null`
- File XML lưu trên disk mở ra có `<CHUKYDONVI><Signature ...>` bên trong
- Tag `<SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256">`

- [ ] **Step 5: Cập nhật .env.example**

```bash
# USB Token Signing Service
USB_TOKEN_SIGN_ENABLED=false
USB_TOKEN_ENDPOINT=http://127.0.0.1:18081/api/EmrSign/SignXmlBhyt
USB_TOKEN_SERVICE_TOKEN=
```

```bash
git add .env.example
git commit -m "docs: add USB Token env vars to .env.example"
```

---

## Kiểm tra tổng thể

- [ ] `dotnet test` → tất cả ≥17 tests PASS (4 PinProvider + 8 XmlSigning + 5 SignEndpoint)
- [ ] `php artisan test` → không có regression
- [ ] Windows Service RUNNING: `sc.exe query UsbTokenSign` → `STATE: 4 RUNNING`
- [ ] Health: `GET http://127.0.0.1:18081/health` → `{"status":"healthy",...}`
- [ ] Signing: test thủ công với XML thật → `Success: true`
- [ ] File XML signed có `<CHUKYDONVI><Signature>` với `SignatureMethod rsa-sha256`
- [ ] HSM fallback: khi `USB_TOKEN_SIGN_ENABLED=false` và `xml_sign.enabled=true` → HSM hoạt động bình thường

---

## Xử lý sự cố thường gặp

| Vấn đề | Cách xử lý |
|--------|-----------|
| Service không start | Xem Event Viewer → Application log |
| Cert không tìm thấy | Kiểm tra driver CA đã cài, cert có trong `certmgr.msc` |
| DPAPI decrypt fail | Chạy encrypt script lại trên đúng máy server |
| PIN sai → CryptographicException | Kiểm tra PIN trong config, service sẽ trả `Success=false` không hang |
| `CheckSignature()` fail | `PreserveWhitespace = true` khi load XML để verify |
| PHP reflection lỗi | Tên property là `httpClient` (camelCase, không phải snake_case) |
